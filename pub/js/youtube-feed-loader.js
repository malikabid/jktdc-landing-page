// YouTube Feed Loader for DOTK Homepage
// Requires: YouTube Data API v3 key (set as YOUTUBE_API_KEY)
// Usage: Place a <section id="youtube-feed-section"></section> in HTML
// and call window.youtubeFeedLoader.initialize({ channelId, maxResults })
(function(global) {
  const YOUTUBE_API_KEY = 'AIzaSyDkzB0hyuNKbUs8kP0cf_HAYVK-eSrm6OI'; // Your API key
  // Use channel ID, not handle. Example: 'UCGZQ6XS3X6PIrZFBX2c_d_w'
  const DEFAULT_CHANNEL_ID = 'UCGZQ6XS3X6PIrZFBX2c_d_w';
  const DEFAULT_MAX_RESULTS = 4;

  // Step 1: Get uploads playlist ID for the channel
  async function fetchUploadsPlaylistId(channelId) {
    const apiUrl = `https://www.googleapis.com/youtube/v3/channels?key=${YOUTUBE_API_KEY}` +
      `&id=${channelId}&part=contentDetails`;
    const response = await fetch(apiUrl);
    if (!response.ok) throw new Error('Failed to fetch channel info');
    const data = await response.json();
    if (!data.items || !data.items.length) throw new Error('Channel not found');
    return data.items[0].contentDetails.relatedPlaylists.uploads;
  }

  // Step 2: Get videos from uploads playlist
  async function fetchLatestVideosFromPlaylist(playlistId, maxResults) {
    const apiUrl = `https://www.googleapis.com/youtube/v3/playlistItems?key=${YOUTUBE_API_KEY}` +
      `&playlistId=${playlistId}&part=snippet&maxResults=${maxResults}`;
    const response = await fetch(apiUrl);
    if (!response.ok) throw new Error('Failed to fetch playlist videos');
    const data = await response.json();
    return data.items;
  }

  function renderVideos(videos, container, channelId) {
    const cardsHtml = videos.map(item => {
      const vid = item.snippet.resourceId.videoId;
      const title = item.snippet.title;
      const thumb = item.snippet.thumbnails && item.snippet.thumbnails.medium ? item.snippet.thumbnails.medium.url : '';
      const url = `https://www.youtube.com/watch?v=${vid}`;
      return `
        <div class="youtube-video-card">
          <a href="${url}" target="_blank" rel="noopener noreferrer">
            <img src="${thumb}" alt="${title}" />
            <!-- <div class="youtube-video-title">${title}</div> -->
          </a>
        </div>
      `;
    }).join('');
    // Add channel link below the videos
    let channelLink = '';
    if (channelId) {
      channelLink = `
        <div class="youtube-channel-link-bar" style="width:100%;background:#181f2a;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:14px 24px 14px 16px;border-radius:0 0 12px 12px;margin-top:18px;box-sizing:border-box;">
          <div style="display:flex;align-items:center;font-size:1rem;gap:8px;">
            <i class='fab fa-youtube' style='color:#ff0000;font-size:1.1rem;'></i>
            <span style="font-weight:500;">Viewing Official Playlist</span>
          </div>
          <a href="https://www.youtube.com/channel/${channelId}" target="_blank" rel="noopener noreferrer" style="color:#fff;font-weight:500;font-size:1rem;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            Visit Channel <i class="fas fa-arrow-up-right-from-square" style="font-size:0.95em;"></i>
          </a>
        </div>
      `;
    }
    container.innerHTML = cardsHtml + channelLink;
  }

  // Fallback: fetch latest videos from channel using search endpoint
  async function fetchLatestVideosFromChannel(channelId, maxResults) {
    const apiUrl = `https://www.googleapis.com/youtube/v3/search?key=${YOUTUBE_API_KEY}` +
      `&channelId=${channelId}&part=snippet,id&order=date&maxResults=${maxResults}&type=video`;
    const response = await fetch(apiUrl);
    if (!response.ok) throw new Error('Failed to fetch channel videos');
    const data = await response.json();
    // Map to playlist-like structure for renderVideos
    return data.items.map(item => ({ snippet: item.snippet, id: item.id }));
  }

  async function initialize({ channelId = DEFAULT_CHANNEL_ID, playlistId = null, maxResults = DEFAULT_MAX_RESULTS } = {}) {
    const section = document.getElementById('youtube-feed-section');
    if (!section) return;
    section.innerHTML = '<div class="youtube-feed-loading">Loading latest videos...</div>';
    try {
      let usePlaylistId = playlistId;
      if (!usePlaylistId) {
        usePlaylistId = await fetchUploadsPlaylistId(channelId);
      }
      const videos = await fetchLatestVideosFromPlaylist(usePlaylistId, maxResults);
      renderVideos(videos, section, channelId);
    } catch (e) {
      // Fallback to channel search if playlist fetch fails
      try {
        const videos = await fetchLatestVideosFromChannel(channelId, maxResults);
        // Patch: renderVideos expects .snippet.resourceId.videoId, so patch structure
        const patchedVideos = videos.map(item => ({
          snippet: {
            ...item.snippet,
            resourceId: { videoId: item.id && item.id.videoId ? item.id.videoId : item.id }
          }
        }));
        renderVideos(patchedVideos, section, channelId);
      } catch (err) {
        section.innerHTML = '<div class="youtube-feed-error">Unable to load YouTube videos.</div>';
      }
    }
  }

  global.youtubeFeedLoader = { initialize };
})(window);