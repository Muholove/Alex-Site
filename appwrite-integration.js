// Appwrite Integration
const APPWRITE = {
    projectId: '6852ab51002ca9bf6bd4',
    databaseId: '681f818100229727cfc0',
    videoCollectionId: '681f81a4001d1281896e',
    thumbnailBucketId: '681f82280005e6182fdd',
    videoBucketId: '681f820d00319f2aa58b',
    apiKey: 'standard_f291de00b8dc2241d3248c9786faa23a9e22f81d3ad95842dc9d955f42464bc179f70721cd83c483dcd5b2d596f529f4a77d67afb37f80040941bb7235a4a5f6e16ad4df4ba8299352e8ec59344efbd8a59da626fd684db28ec14221428d21c57c566b52ad84c6dde8055f3c189e93d347200a4778a065dae271c14c4105db0e',
    endpoint: 'https://cloud.appwrite.io/v1'
};

// Appwrite SDK initialization
let appwriteApi = {
    // Get all videos from Appwrite database
    getVideos: async function() {
        try {
            const response = await fetch(`${APPWRITE.endpoint}/databases/${APPWRITE.databaseId}/collections/${APPWRITE.videoCollectionId}/documents`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Appwrite-Project': APPWRITE.projectId,
                    'X-Appwrite-Key': APPWRITE.apiKey
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const data = await response.json();
            return {
                success: true,
                videos: data.documents || []
            };
        } catch (error) {
            console.error('Error fetching videos:', error);
            return {
                success: false,
                message: error.message,
                videos: []
            };
        }
    },

    // Get a specific video by ID
    getVideo: async function(videoId) {
        try {
            const response = await fetch(`${APPWRITE.endpoint}/databases/${APPWRITE.databaseId}/collections/${APPWRITE.videoCollectionId}/documents/${videoId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Appwrite-Project': APPWRITE.projectId,
                    'X-Appwrite-Key': APPWRITE.apiKey
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const data = await response.json();
            return {
                success: true,
                video: data
            };
        } catch (error) {
            console.error('Error fetching video:', error);
            return {
                success: false,
                message: error.message
            };
        }
    },

    // Get file preview URL from Appwrite storage
    getFilePreviewUrl: function(bucketId, fileId, width = 400) {
        return `${APPWRITE.endpoint}/storage/buckets/${bucketId}/files/${fileId}/preview?width=${width}&project=${APPWRITE.projectId}`;
    },

    // Get file view URL from Appwrite storage
    getFileViewUrl: function(bucketId, fileId) {
        return `${APPWRITE.endpoint}/storage/buckets/${bucketId}/files/${fileId}/view?project=${APPWRITE.projectId}`;
    }
}; 