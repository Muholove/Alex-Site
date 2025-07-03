// Appwrite Integration Module
const AppwriteIntegration = {
    // Configuration with default values
    config: {
        endpoint: 'https://cloud.appwrite.io/v1',
        projectId: '6852ab51002ca9bf6bd4',
        databaseId: '681f818100229727cfc0',
        videoCollectionId: '681f81a4001d1281896e',
        thumbnailBucketId: '681f82280005e6182fdd',
        videoBucketId: '681f820d00319f2aa58b'
    },

    /**
     * Debug mode for additional console logs
     */
    debug: true,

    /**
     * Initialize the Appwrite Integration with optional configuration overrides
     * @param {Object} config Optional configuration override
     * @returns {Object} The AppwriteIntegration object for chaining
     */
    init(config = {}) {
        this.config = { ...this.config, ...config };
        if (this.debug) {
            console.log('AppwriteIntegration initialized with config:', this.config);
        }
        return this;
    },

    /**
     * Get all videos from the Appwrite database
     * @returns {Promise<Array>} Array of video objects
     */
    async getVideos() {
        try {
            if (this.debug) {
                console.log('Fetching videos from API endpoint:', '/get_videos.php');
            }
            
            const response = await fetch('/get_videos.php');
            
            if (this.debug) {
                console.log('API Response status:', response.status, response.statusText);
            }
            
            if (!response.ok) {
                let errorText = '';
                try {
                    const errorData = await response.text();
                    errorText = errorData;
                    console.error('Error response content:', errorData);
                } catch (e) {
                    console.error('Failed to read error response:', e);
                }
                throw new Error(`Error loading videos: HTTP ${response.status} - ${errorText}`);
            }
            
            const data = await response.json();
            
            if (this.debug) {
                console.log('Parsed API response:', data);
            }
            
            if (!data.success) {
                throw new Error(data.message || 'Unknown error from API');
            }
            
            return data.videos || [];
        } catch (error) {
            console.error('Error in getVideos:', error);
            throw new Error('Error loading videos: ' + error.message);
        }
    },

    /**
     * Get a single video by ID
     * @param {string} id The video ID
     * @returns {Promise<Object>} Video object
     */
    async getVideo(id) {
        try {
            if (this.debug) console.log(`Fetching video with ID: ${id}`);
            
            const response = await fetch(`/get_video.php?id=${id}`);
            
            if (this.debug) {
                console.log('API Response status for getVideo:', response.status, response.statusText);
            }
            
            if (!response.ok) {
                let errorText = '';
                try {
                    errorText = await response.text();
                    console.error('Error response content:', errorText);
                } catch (e) {
                    console.error('Failed to read error response:', e);
                }
                throw new Error(`Error loading video: HTTP ${response.status} - ${errorText}`);
            }
            
            const data = await response.json();
            
            if (this.debug) {
                console.log('Parsed video response:', data);
            }
            
            if (!data.success) {
                throw new Error(data.message || 'Unknown error fetching video');
            }
            
            return data.video;
        } catch (error) {
            console.error('Error in getVideo:', error);
            throw new Error('Error loading video: ' + error.message);
        }
    },
    
    /**
     * Search videos by title and description
     * @param {string} query Search query
     * @returns {Promise<Array>} Array of matching video objects
     */
    async searchVideos(query) {
        try {
            if (!query || query.trim() === '') {
                return this.getVideos();
            }
            
            if (this.debug) console.log(`Searching videos with query: "${query}"`);
            
            // Get all videos and filter on the client side
            const allVideos = await this.getVideos();
            
            if (this.debug) console.log(`Got ${allVideos.length} videos, filtering by query`);
            
            const searchQuery = query.toLowerCase();
            const filtered = allVideos.filter(video => 
                (video.title && video.title.toLowerCase().includes(searchQuery)) || 
                (video.description && video.description.toLowerCase().includes(searchQuery))
            );
            
            if (this.debug) console.log(`Found ${filtered.length} videos matching query`);
            
            return filtered;
        } catch (error) {
            console.error('Error in searchVideos:', error);
            throw new Error('Error searching videos: ' + error.message);
        }
    }
};

// Export the module
window.AppwriteIntegration = AppwriteIntegration; 