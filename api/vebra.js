/**
 * Vercel Serverless Function: /api/vebra
 * 
 * Proxies requests to the Vebra Alto API.
 * Handles token-based authentication (initial Basic Auth → token reuse).
 * 
 * ──────────────────────────────────────────────
 * SETUP: Set these Vercel Environment Variables:
 * ──────────────────────────────────────────────
 *   VEBRA_DATA_FEED_ID   — Your Vebra data feed ID (e.g. "12345")
 *   VEBRA_USERNAME        — Vebra API username
 *   VEBRA_PASSWORD        — Vebra API password
 *   VEBRA_BASE_URL        — (Optional) Override base URL. Default: https://webservices.vebra.com/export/{feedId}/v1
 * 
 * ──────────────────────────────────────────────
 * API Endpoints (query param: action)
 * ──────────────────────────────────────────────
 *   GET /api/vebra?action=branches         — List all branches
 *   GET /api/vebra?action=branch&id=123    — Get specific branch
 *   GET /api/vebra?action=properties&branchId=123  — List properties for a branch
 *   GET /api/vebra?action=property&branchId=123&id=456 — Get single property
 *   GET /api/vebra?action=changedproperties&since=2025-01-01  — Changed since date
 *   GET /api/vebra?action=search&...       — Search with filters (proxied to frontend filtering)
 * 
 * ──────────────────────────────────────────────
 * Vebra Alto API Overview
 * ──────────────────────────────────────────────
 * Base URL: https://webservices.vebra.com/export/{dataFeedId}/v1
 * 
 * Auth flow:
 *   1. POST /token with Basic Auth header → returns Bearer token
 *   2. All subsequent requests use Authorization: Bearer {token}
 *   3. Token expires, re-auth on 401
 * 
 * Key endpoints:
 *   GET /branch                — List branches (XML)
 *   GET /branch/{branchId}     — Branch details
 *   GET /branch/{branchId}/property — List properties for branch
 *   GET /branch/{branchId}/property/{propId} — Property details (inc. images, EPC, etc.)
 *   GET /branch/{branchId}/property/{propId}/changedproperties/{yyyy-MM-dd} — Delta feed
 * 
 * Response format: XML by default, parsed here and returned as JSON.
 */

// Simple in-memory token cache (per cold start)
let cachedToken = null;
let tokenExpiry = 0;

module.exports = async function handler(req, res) {
  // CORS headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  const {
    VEBRA_DATA_FEED_ID,
    VEBRA_USERNAME,
    VEBRA_PASSWORD,
    VEBRA_BASE_URL
  } = process.env;

  // ─── Config check ───
  if (!VEBRA_DATA_FEED_ID || !VEBRA_USERNAME || !VEBRA_PASSWORD) {
    return res.status(200).json({
      source: 'placeholder',
      message: 'Vebra API not configured. Set VEBRA_DATA_FEED_ID, VEBRA_USERNAME, VEBRA_PASSWORD in Vercel env vars.',
      configured: false,
      properties: []
    });
  }

  const baseUrl = VEBRA_BASE_URL || `https://webservices.vebra.com/export/${VEBRA_DATA_FEED_ID}/v1`;
  const action = req.query.action || 'properties';

  try {
    // ─── Get/refresh token ───
    const token = await getToken(baseUrl, VEBRA_USERNAME, VEBRA_PASSWORD);

    // ─── Route to correct endpoint ───
    let apiPath = '';
    const branchId = req.query.branchId || req.query.branch_id;

    switch (action) {
      case 'branches':
        apiPath = '/branch';
        break;
      
      case 'branch':
        if (!req.query.id) return res.status(400).json({ error: 'Missing id parameter' });
        apiPath = `/branch/${req.query.id}`;
        break;
      
      case 'properties':
        if (!branchId) {
          // If no branchId, fetch all branches then all properties
          const branches = await vebraFetch(`${baseUrl}/branch`, token);
          const branchList = parseBranches(branches);
          
          let allProperties = [];
          for (const branch of branchList) {
            try {
              const propsXml = await vebraFetch(`${baseUrl}/branch/${branch.id}/property`, token);
              const props = parsePropertyList(propsXml, branch);
              
              // Fetch full details for each property
              for (const prop of props) {
                try {
                  const detailXml = await vebraFetch(prop.url || `${baseUrl}/branch/${branch.id}/property/${prop.id}`, token);
                  const detail = parsePropertyDetail(detailXml, branch);
                  if (detail) allProperties.push(detail);
                } catch (e) {
                  console.error(`Failed to fetch property ${prop.id}:`, e.message);
                }
              }
            } catch (e) {
              console.error(`Failed to fetch properties for branch ${branch.id}:`, e.message);
            }
          }

          // Apply search filters from query params
          allProperties = applySearchFilters(allProperties, req.query);

          return res.status(200).json({
            source: 'vebra',
            configured: true,
            total: allProperties.length,
            properties: allProperties
          });
        }
        apiPath = `/branch/${branchId}/property`;
        break;

      case 'property':
        if (!branchId || !req.query.id) {
          return res.status(400).json({ error: 'Missing branchId and/or id parameter' });
        }
        apiPath = `/branch/${branchId}/property/${req.query.id}`;
        break;

      case 'changedproperties':
        if (!branchId) return res.status(400).json({ error: 'Missing branchId parameter' });
        const since = req.query.since || new Date(Date.now() - 7 * 86400000).toISOString().split('T')[0];
        apiPath = `/branch/${branchId}/property/changedproperties/${since}`;
        break;

      default:
        return res.status(400).json({ error: `Unknown action: ${action}` });
    }

    // ─── Fetch from Vebra ───
    const xmlData = await vebraFetch(`${baseUrl}${apiPath}`, token);

    // Parse based on action
    let parsed;
    switch (action) {
      case 'branches':
        parsed = { branches: parseBranches(xmlData) };
        break;
      case 'branch':
        parsed = { branch: parseBranchDetail(xmlData) };
        break;
      case 'properties':
        parsed = { properties: parsePropertyList(xmlData, { id: branchId }) };
        break;
      case 'property':
        parsed = { property: parsePropertyDetail(xmlData, { id: branchId }) };
        break;
      case 'changedproperties':
        parsed = { properties: parsePropertyList(xmlData, { id: branchId }) };
        break;
      default:
        parsed = { raw: xmlData };
    }

    return res.status(200).json({
      source: 'vebra',
      configured: true,
      ...parsed
    });

  } catch (err) {
    console.error('Vebra API error:', err);
    return res.status(500).json({
      source: 'vebra',
      error: err.message,
      configured: true
    });
  }
};


// ──────────────────────────────────────────────
// Authentication
// ──────────────────────────────────────────────

async function getToken(baseUrl, username, password) {
  // Return cached token if still valid (with 60s buffer)
  if (cachedToken && Date.now() < tokenExpiry - 60000) {
    return cachedToken;
  }

  const authString = Buffer.from(`${username}:${password}`).toString('base64');
  
  const response = await fetch(`${baseUrl}/token`, {
    method: 'GET',
    headers: {
      'Authorization': `Basic ${authString}`,
      'Accept': 'application/xml'
    }
  });

  if (!response.ok) {
    const body = await response.text();
    throw new Error(`Vebra auth failed (${response.status}): ${body}`);
  }

  const tokenXml = await response.text();
  
  // Extract token from XML: <token>...</token>
  const tokenMatch = tokenXml.match(/<token[^>]*>([^<]+)<\/token>/i);
  if (!tokenMatch) {
    throw new Error('Could not parse token from Vebra response');
  }

  cachedToken = tokenMatch[1].trim();
  // Token typically lasts 1 hour, cache for 55 mins
  tokenExpiry = Date.now() + 55 * 60 * 1000;
  
  return cachedToken;
}


// ──────────────────────────────────────────────
// HTTP fetch helper with token auth + retry on 401
// ──────────────────────────────────────────────

async function vebraFetch(url, token, retried = false) {
  const response = await fetch(url, {
    headers: {
      'Authorization': `Basic ${token}`,
      'Accept': 'application/xml'
    }
  });

  if (response.status === 401 && !retried) {
    // Token expired, clear cache and caller should re-auth
    cachedToken = null;
    tokenExpiry = 0;
    throw new Error('Token expired — will re-auth on next request');
  }

  if (!response.ok) {
    throw new Error(`Vebra API ${response.status}: ${await response.text()}`);
  }

  return await response.text();
}


// ──────────────────────────────────────────────
// XML Parsers (lightweight, no dependencies)
// ──────────────────────────────────────────────

function extractTag(xml, tag) {
  const re = new RegExp(`<${tag}[^>]*>([\\s\\S]*?)<\\/${tag}>`, 'i');
  const m = xml.match(re);
  return m ? m[1].trim() : '';
}

function extractAllTags(xml, tag) {
  const re = new RegExp(`<${tag}[^>]*>[\\s\\S]*?<\\/${tag}>`, 'gi');
  return xml.match(re) || [];
}

function extractAttr(xml, attr) {
  const re = new RegExp(`${attr}="([^"]*)"`, 'i');
  const m = xml.match(re);
  return m ? m[1] : '';
}

function parseBranches(xml) {
  const branches = extractAllTags(xml, 'branch');
  return branches.map(b => ({
    id: extractTag(b, 'branchid') || extractAttr(b, 'id'),
    name: extractTag(b, 'name'),
    url: extractTag(b, 'url') || extractAttr(b, 'url')
  }));
}

function parseBranchDetail(xml) {
  return {
    id: extractTag(xml, 'branchid'),
    name: extractTag(xml, 'name'),
    address: extractTag(xml, 'street') + ', ' + extractTag(xml, 'town'),
    postcode: extractTag(xml, 'postcode'),
    phone: extractTag(xml, 'phone'),
    email: extractTag(xml, 'email')
  };
}

function parsePropertyList(xml, branch) {
  const props = extractAllTags(xml, 'property');
  return props.map(p => ({
    id: extractTag(p, 'prop_id') || extractAttr(p, 'id'),
    url: extractTag(p, 'url') || extractAttr(p, 'url'),
    lastChanged: extractTag(p, 'lastchanged'),
    branchId: branch?.id
  }));
}

/**
 * Parse a full property detail XML into our normalised format.
 * This maps Vebra's XML schema to the same structure the frontend expects.
 * 
 * Vebra property XML typically contains:
 *   <property>
 *     <prop_id>, <ref>, <address> (with <street>, <town>, <postcode>, <county>)
 *     <price qualifier="Guide Price">325000</price>
 *     <bedrooms>, <bathrooms>, <receptions>, <tenure>
 *     <type>, <style>, <status>
 *     <description>, <paragraphs><paragraph>...</paragraph></paragraphs>
 *     <area> (with <measure>, <min>, <max>)
 *     <epc> (with <current_energy_rating>)
 *     <files><file> (images, floorplans, EPCs with <url>, <type>)
 *     <bullets><bullet>...</bullet></bullets>
 *     <council_tax_band>, <parking>, <garden>
 *     <latitude>, <longitude>
 *     <date_added>, <date_updated>
 *   </property>
 */
function parsePropertyDetail(xml, branch) {
  if (!xml) return null;

  const propId = extractTag(xml, 'prop_id') || extractTag(xml, 'propertyid');
  if (!propId) return null;

  // Address
  const street = extractTag(xml, 'street') || extractTag(xml, 'display_address') || '';
  const town = extractTag(xml, 'town') || '';
  const county = extractTag(xml, 'county') || '';
  const postcode = extractTag(xml, 'postcode') || '';
  const address = [street, town, county, postcode].filter(Boolean).join(', ');

  // Price
  const priceRaw = extractTag(xml, 'price') || '0';
  const price = parseInt(priceRaw.replace(/[^0-9]/g, '')) || 0;
  const priceQualifier = extractAttr(xml.match(/<price[^>]*>/)?.[0] || '', 'qualifier') || 'Guide Price';

  // Beds, baths, receptions
  const beds = parseInt(extractTag(xml, 'bedrooms')) || 0;
  const baths = parseInt(extractTag(xml, 'bathrooms')) || 0;
  const reception = parseInt(extractTag(xml, 'receptions') || extractTag(xml, 'reception_rooms')) || 0;

  // Type and style
  const propType = extractTag(xml, 'type') || '';
  const propStyle = extractTag(xml, 'style') || '';
  const subtype = propStyle || propType || 'Property';

  // Map type to our categories
  let type = 'house';
  const typeLower = (propType + ' ' + propStyle).toLowerCase();
  if (typeLower.includes('flat') || typeLower.includes('apartment') || typeLower.includes('penthouse') || typeLower.includes('maisonette')) {
    type = 'flat';
  } else if (typeLower.includes('bungalow')) {
    type = 'bungalow';
  }

  // Area / sqft
  const areaMin = extractTag(xml, 'min') || extractTag(xml, 'area');
  const areaMeasure = extractTag(xml, 'measure') || 'sqft';
  let sqft = parseInt(areaMin) || 0;
  if (areaMeasure.toLowerCase().includes('sqm') || areaMeasure.toLowerCase().includes('metre')) {
    sqft = Math.round(sqft * 10.764); // convert sqm to sqft
  }

  // Description
  const mainDesc = extractTag(xml, 'description') || '';
  const paragraphs = extractAllTags(xml, 'paragraph').map(p => extractTag(p, 'paragraph') || p.replace(/<\/?paragraph>/gi, '').trim()).filter(Boolean);
  const description = mainDesc || paragraphs[0] || '';

  // Key features / bullets
  const bullets = extractAllTags(xml, 'bullet').map(b => b.replace(/<\/?bullet>/gi, '').trim()).filter(Boolean);

  // Images
  const files = extractAllTags(xml, 'file');
  const images = [];
  const floorplanImages = [];
  
  for (const f of files) {
    const fileUrl = extractTag(f, 'url') || '';
    const fileType = (extractTag(f, 'type') || extractTag(f, 'name') || '').toLowerCase();
    
    if (!fileUrl) continue;
    
    if (fileType.includes('floorplan') || fileType.includes('floor_plan')) {
      floorplanImages.push(fileUrl);
    } else if (fileType.includes('image') || fileType.includes('photo') || fileType.includes('.jpg') || fileType.includes('.jpeg') || fileType.includes('.png') || fileUrl.match(/\.(jpg|jpeg|png|webp)/i)) {
      images.push(fileUrl);
    } else {
      // Default: treat as image
      images.push(fileUrl);
    }
  }

  // EPC
  const epcRating = extractTag(xml, 'current_energy_rating') || extractTag(xml, 'epc_rating') || '';

  // Location
  const lat = parseFloat(extractTag(xml, 'latitude')) || null;
  const lng = parseFloat(extractTag(xml, 'longitude')) || null;

  // Dates
  const dateAdded = extractTag(xml, 'date_added') || extractTag(xml, 'available_date') || '';
  const dateUpdated = extractTag(xml, 'date_updated') || '';

  // Other fields
  const tenure = extractTag(xml, 'tenure') || '';
  const councilTax = extractTag(xml, 'council_tax_band') || extractTag(xml, 'council_tax') || '';
  const parking = extractTag(xml, 'parking') || '';
  const garden = extractTag(xml, 'garden') || '';
  const status = extractTag(xml, 'status') || 'sale';

  // Format date for display (try to parse ISO → DD/MM/YYYY)
  let displayDate = dateAdded;
  try {
    if (dateAdded && dateAdded.includes('-')) {
      const d = new Date(dateAdded);
      if (!isNaN(d)) {
        displayDate = `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
      }
    }
  } catch(e) {}

  return {
    id: parseInt(propId) || propId,
    title: `${beds} bedroom ${subtype.toLowerCase()} for ${status === 'sale' ? 'sale' : 'rent'}`,
    address,
    area: town || county || '',
    price,
    priceDisplay: `£${price.toLocaleString()}`,
    priceLabel: priceQualifier,
    status: status.toLowerCase().includes('rent') ? 'rent' : 'sale',
    type,
    subtype,
    beds,
    baths,
    reception,
    sqft,
    featured: false,
    badge: '',
    dateAdded: displayDate,
    dateUpdated,
    tenure,
    councilTax,
    parking: parking ? 'Yes' : 'Ask agent',
    garden: garden ? 'Yes' : 'Ask agent',
    accessibility: 'Ask agent',
    epcRating: epcRating.toUpperCase(),
    distance: '',
    description: description.substring(0, 300) + (description.length > 300 ? '...' : ''),
    fullDescription: paragraphs.length > 0 ? paragraphs : [description],
    keyFeatures: bullets,
    images: images.length > 0 ? images : ['https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&h=500&fit=crop'],
    floorplanImages,
    lat,
    lng,
    // Keep the raw Vebra reference for debugging
    _vebraRef: extractTag(xml, 'ref') || propId,
    _branchId: branch?.id
  };
}


// ──────────────────────────────────────────────
// Server-side search filters
// ──────────────────────────────────────────────

function applySearchFilters(properties, query) {
  let filtered = [...properties];

  // Text search
  if (query.q) {
    const q = query.q.toLowerCase();
    filtered = filtered.filter(p =>
      p.address.toLowerCase().includes(q) ||
      p.area.toLowerCase().includes(q) ||
      p.subtype.toLowerCase().includes(q)
    );
  }

  // Property type
  if (query.type) {
    filtered = filtered.filter(p => p.type === query.type);
  }

  // Price range
  if (query.priceMin) {
    const min = parseInt(query.priceMin);
    if (min) filtered = filtered.filter(p => p.price >= min);
  }
  if (query.priceMax) {
    const max = parseInt(query.priceMax);
    if (max) filtered = filtered.filter(p => p.price <= max);
  }

  // Beds range
  if (query.bedsMin) {
    const min = parseInt(query.bedsMin);
    if (min) filtered = filtered.filter(p => p.beds >= min);
  }
  if (query.bedsMax) {
    const max = parseInt(query.bedsMax);
    if (max) filtered = filtered.filter(p => p.beds <= max);
  }

  // Status (sale/rent)
  if (query.status) {
    filtered = filtered.filter(p => p.status === query.status);
  }

  // Sort
  if (query.sort) {
    switch (query.sort) {
      case 'price-asc': filtered.sort((a, b) => a.price - b.price); break;
      case 'price-desc': filtered.sort((a, b) => b.price - a.price); break;
      case 'newest': filtered.sort((a, b) => (b.dateUpdated || b.dateAdded).localeCompare(a.dateUpdated || a.dateAdded)); break;
      case 'beds-desc': filtered.sort((a, b) => b.beds - a.beds); break;
    }
  }

  return filtered;
}
