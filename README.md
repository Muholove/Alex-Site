# ADULTFLIX - Appwrite Configuration

This project uses Appwrite as the backend. The configuration is set up to work with Appwrite cloud services.

## Configuration

The Appwrite configuration has been centralized into these files:

- `config.php` - Server-side configuration for PHP files
- `config.json` - Contains both client-side and server-side configuration 
- `config-loader.js` - Loads configuration from the server to the client

### Appwrite Settings

The following Appwrite settings are used:

- **Project ID**: 6852ab51002ca9bf6bd4
- **Database ID**: 681f818100229727cfc0
- **Video Collection ID**: 681f81a4001d1281896e
- **Thumbnail Bucket ID**: 681f82280005e6182fdd
- **Video Bucket ID**: 681f820d00319f2aa58b
- **API Key**: [Stored in config.php and config.json]

## How It Works

1. Server-side PHP files load configuration from `config.php`
2. Client-side scripts load configuration from `get_config.php` which reads `config.json`
3. The API key is only used server-side and is not exposed to the client

## Pages

- **index.html** - Main page that displays all videos
- **preview.html** - Video preview and purchase page

## Configuration Files Usage

### In PHP Files
```php
// Load configuration
$appwrite = require 'config.php';

// Access configuration values
$endpoint = $appwrite['endpoint'];
$projectId = $appwrite['projectId'];
```

### In JavaScript Files
```javascript
// The config is loaded through config-loader.js
// Access configuration values
const config = window.siteConfig.appwrite;
const projectId = config.projectId;
```

## Security Notes

- The API key is only used on the server side
- Client-side configuration does not include the API key
- All API requests go through PHP backend files to protect the API key 