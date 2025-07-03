# Video Site with Appwrite Integration

This project has been migrated to use Appwrite for video and image storage, as well as database functionality. The local video files are no longer used, and instead, content is served directly from Appwrite storage buckets.

## Configuration

The Appwrite configuration is stored in the following files:
- `appwrite-integration.js` - Main JavaScript integration file
- `get_videos.php` and `get_video.php` - PHP endpoints for fetching video data

### Appwrite Resources Used:
- Project ID: 6852ab51002ca9bf6bd4
- Database ID: 681f818100229727cfc0
- Video Collection ID: 681f81a4001d1281896e
- Thumbnail Bucket ID: 681f82280005e6182fdd
- Video Bucket ID: 681f820d00319f2aa58b

## Data Structure

### Video Collection Structure
The video collection in Appwrite should have the following attributes:

| Attribute | Type | Description |
|-----------|------|-------------|
| title | String | Title of the video |
| description | String | Description of the video |
| price | Double | Price for purchasing the video |
| product_link | String | Link to purchase the video |
| video_id | String | ID of the video file in the video bucket |
| thumbnail_id | String | ID of the thumbnail image in the thumbnail bucket |
| created_at | DateTime | When the video was created |
| is_active | Boolean | Whether the video is active and should be displayed |
| duration | Integer | Duration of the video in seconds |
| views | Integer | Number of views |

## How to Upload Content

Videos should be uploaded directly through the Appwrite console or API:

1. Upload the video to the Video Bucket
2. Upload the thumbnail to the Thumbnail Bucket
3. Create a document in the Video Collection with the proper attributes

## Backward Compatibility

The system still supports local files for backward compatibility. Files in the `/videos` and `/imagens` folders can still be accessed, but new content should be added to Appwrite.

## File Structure

- `index (1).html` - Main listing page that displays videos
- `preview.html` - Page for viewing video details and purchase options
- `appwrite-integration.js` - JavaScript functions for Appwrite interaction
- `get_videos.php` - PHP endpoint that fetches all videos from Appwrite
- `get_video.php` - PHP endpoint that fetches a single video or media file
- `config.json` - Site configuration

## Removed Dependencies

The following files/folders are no longer needed and can be removed:
- `videos.json` - No longer used to store video data
- Local video files in the `/videos` folder
- Local thumbnail files in the `/imagens` folder
- `upload_media.php` - No longer needed for uploads
- `check_videos.php` - No longer needed for validation

## Future Improvements

- Create an admin interface to manage videos directly through Appwrite
- Add user authentication through Appwrite Authentication
- Implement server-side rendering for better SEO 