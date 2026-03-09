# Cold Lava Property Search

A WordPress plugin that adds a full property search page with Vebra Alto API integration. Drop it into any page using a simple shortcode.

## Installation

1. Download the `cold-lava-property-search.zip` file
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Choose the zip file and click **Install Now**
4. Click **Activate Plugin**

That's it -- the plugin is now active.

## Adding the Property Search to a Page

1. Create a new page (or edit an existing one)
2. Add the following shortcode wherever you want the property search to appear:

```
[cl_property_search]
```

**Using Elementor?** Add a "Shortcode" widget and paste `[cl_property_search]` into it. For best results, set the page template to "Full Width" or "Elementor Full Width".

**Tip:** We recommend using a full-width page template so the property search has maximum space.

## Configuration

### Basic Setup (No Vebra)

The plugin works out of the box with placeholder properties. This is great for previewing the layout before your Vebra API credentials are ready.

### Connecting to Vebra Alto

1. Go to **Settings > Property Search** in your WordPress admin
2. Enter your Vebra API credentials:
   - **Data Feed ID** -- Your Vebra data feed ID (a number like "12345")
   - **Username** -- Your Vebra API username
   - **Password** -- Your Vebra API password
3. Click **Save Changes**
4. Click **Test Vebra API Connection** to verify everything works
5. If successful, your live properties will appear on the search page

### Agent Details

In the same settings page, fill in:
- **Agent Name** -- Shown on property cards (e.g. "Bernards Estate Agents, Southsea")
- **Agent Phone** -- Phone number shown on property cards and detail views
- **Agent Email** -- Email address for enquiries

### Display Settings

- **Show Placeholder Properties** -- When enabled (default), sample Portsmouth properties are shown if Vebra is not configured or returns no results. Turn this off once you are live with Vebra.

## Getting Vebra API Credentials

If you do not yet have API credentials:

1. Contact your Vebra Alto account manager or Vebra support
2. Request API access for the "Web Services" / "Data Feed" feature
3. They will provide you with:
   - A Data Feed ID
   - A Username
   - A Password
4. These are different from your normal Vebra login credentials

**Vebra Support:** support@vebra.com / 01onal contact your account manager

## Features

- **Full property search** with filters: location, radius, price range, bedrooms, property type
- **Responsive design** -- works on desktop, tablet, and mobile
- **Property detail modal** -- click any property card to see full details
- **Vebra Alto integration** -- live properties from your CRM
- **Smart caching** -- properties are cached for 15 minutes to keep pages fast
- **Placeholder mode** -- preview the layout before going live
- **Rightmove-style layout** -- familiar design your visitors already know

## Troubleshooting

### Properties are not loading

1. Check that the page contains the shortcode `[cl_property_search]`
2. Open your browser console (F12) and look for JavaScript errors
3. Make sure jQuery is loaded (it is included with WordPress by default)

### Vebra connection fails

1. Go to **Settings > Property Search** and verify all three fields are filled in
2. Click **Save Changes** first, then **Test Connection**
3. Check that your WordPress server can make outbound HTTPS requests (some hosts block this)
4. Verify your credentials are correct -- they are different from your Vebra CRM login
5. If you see "Authentication failed", double-check username/password with Vebra support

### Properties show but images are missing

- Vebra properties without images will show a placeholder image
- Check that your Vebra feed includes image URLs
- Image URLs must be publicly accessible

### The search page looks wrong with my theme

- Use a "Full Width" or "Elementor Full Width" page template
- The plugin includes its own CSS that should work with most themes
- If styles conflict, you may need minor CSS adjustments

### Clearing the cache

Go to **Settings > Property Search** and click **Clear Property Cache**. This forces a fresh fetch from Vebra on the next page load.

### Plugin conflicts

The plugin uses the `clps-` prefix for all CSS classes and JavaScript to avoid conflicts. If you experience issues:
1. Temporarily deactivate other plugins to identify conflicts
2. Check for JavaScript errors in the browser console

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Works with Elementor and most WordPress themes
- Outbound HTTPS access (for Vebra API calls)

## Support

For plugin support, contact Cold Lava: hello@coldlava.co.uk

---

Powered by Cold Lava
