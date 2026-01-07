// Real-time Notification System with Server-Sent Events (SSE)
let notificationCount = 0;
let notificationCheckInterval;
let eventSource = null;
let useSSE = true; // Toggle to use SSE or fallback to polling

// Update notification badge and count
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    const countText = document.querySelector('.notification-count-text');
    
    if (count > 0) {
        if (!badge) {
            // Create badge if doesn't exist
            const bellButton = document.querySelector('.notification-bell-button');
            if (bellButton) {
                const newBadge = document.createElement('span');
                newBadge.className = 'notification-badge absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center';
                newBadge.textContent = count > 9 ? '9+' : count;
                bellButton.appendChild(newBadge);
            }
        } else {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = 'flex';
        }
        
        if (countText) {
            countText.textContent = count;
            countText.parentElement.style.display = 'inline-block';
        }
    } else {
        if (badge) {
            badge.style.display = 'none';
        }
        if (countText && countText.parentElement) {
            countText.parentElement.style.display = 'none';
        }
    }
    
    notificationCount = count;
}

// Fetch notifications from API
async function fetchNotifications() {
    try {
        const response = await fetch('/prcf_keuangan/api/api_notifications.php?action=get');
        const data = await response.json();
        
        if (data.success) {
            // Update badge count
            updateNotificationBadge(data.total_count);
            
            // Update notification panel if open
            updateNotificationPanel(data.notifications, data.total_count);
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
}

// Update notification panel content
function updateNotificationPanel(notifications, totalCount) {
    const panel = document.getElementById('notificationPanel');
    if (!panel || panel.classList.contains('hidden')) {
        return; // Don't update if panel is closed
    }
    
    const container = panel.querySelector('.max-h-96');
    if (!container) return;
    
    // Clear current content
    container.innerHTML = '';
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-center text-gray-500 text-sm">
                <i class="fas fa-inbox text-3xl mb-2"></i>
                <p>Tidak ada notifikasi</p>
            </div>
        `;
    } else {
        notifications.forEach(notif => {
            const notifElement = createNotificationElement(notif);
            container.appendChild(notifElement);
        });
    }
}

// Create notification element
function createNotificationElement(notif) {
    const a = document.createElement('a');
    a.href = notif.link;
    a.className = 'block p-4 border-b border-gray-100 transition bg-blue-50 hover:bg-blue-100';
    
    let iconClass = 'fas fa-file-alt text-blue-500';
    if (notif.type === 'report') {
        iconClass = 'fas fa-chart-line text-green-500';
    } else if (notif.type === 'success') {
        iconClass = 'fas fa-check-circle text-green-500';
    } else if (notif.type === 'warning') {
        iconClass = 'fas fa-exclamation-circle text-yellow-500';
    }
    
    a.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0 mr-3">
                <i class="${iconClass} text-lg"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-gray-900 font-bold">
                    ${notif.title || 'Tidak ada judul'}
                </p>
                ${notif.message ? `<p class="text-xs text-gray-600 mt-1">${notif.message}</p>` : ''}
                <p class="text-xs text-gray-500 mt-1">
                    <i class="far fa-clock mr-1"></i>${formatTime(notif.time || 'Tidak diketahui')}
                </p>
            </div>
            ${notif.is_unread ? '<div class="flex-shrink-0 ml-2"><span class="w-2 h-2 bg-blue-600 rounded-full inline-block"></span></div>' : ''}
        </div>
    `;
    
    // Add click event to clear badge
    a.addEventListener('click', function(e) {
        onNotificationClick(e);
    });
    
    return a;
}

// Format time for display
function formatTime(timeString) {
    // If time is undefined or null, return default
    if (!timeString) {
        return 'Tidak diketahui';
    }
    
    // If it's already a formatted string (contains "yang lalu" or "lalu" or "Baru saja"), return as is
    if (typeof timeString === 'string' && (timeString.includes('yang lalu') || timeString.includes('lalu') || timeString.includes('Baru saja') || timeString.includes('Tidak diketahui'))) {
        return timeString;
    }
    
    // Otherwise, try to parse as date and format
    try {
        const date = new Date(timeString);
        if (isNaN(date.getTime())) {
            return timeString; // Return as-is if not a valid date
        }
        
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Baru saja';
        if (diffMins < 60) return diffMins + ' menit yang lalu';
        if (diffHours < 24) return diffHours + ' jam yang lalu';
        if (diffDays < 7) return diffDays + ' hari yang lalu';
        
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (e) {
        return timeString || 'Tidak diketahui';
    }
}

// Mark notifications as read
async function markNotificationsAsRead() {
    try {
        const response = await fetch('/prcf_keuangan/api/api_notifications.php?action=mark_read');
        const data = await response.json();
        
        if (data.success) {
            // Hide all blue indicators
            document.querySelectorAll('.notification-badge').forEach(badge => {
                badge.style.display = 'none';
            });
            
            // Remove blue background from notifications
            document.querySelectorAll('.bg-blue-50').forEach(elem => {
                elem.classList.remove('bg-blue-50', 'hover:bg-blue-100');
                elem.classList.add('bg-white', 'hover:bg-gray-50');
            });
            
            // Remove unread indicators
            document.querySelectorAll('.bg-blue-600').forEach(indicator => {
                indicator.remove();
            });
            
            // Update badge to 0
            updateNotificationBadge(0);
        }
    } catch (error) {
        console.error('Error marking notifications as read:', error);
    }
}

// Enhanced toggleNotifications with mark as read
function toggleNotificationsRealtime() {
    const panel = document.getElementById('notificationPanel');
    const profilePanel = document.getElementById('profilePanel');
    profilePanel.classList.add('hidden');
    
    const wasHidden = panel.classList.contains('hidden');
    panel.classList.toggle('hidden');
    
    // If opening panel, immediately clear badge
    if (wasHidden && !panel.classList.contains('hidden')) {
        // Clear badge immediately when opening panel
        updateNotificationBadge(0);
        
        // Mark notifications as read after viewing
        setTimeout(() => {
            markNotificationsAsRead();
        }, 1000);
    }
}

// Initialize Server-Sent Events connection with improved retry mechanism
function initializeSSE() {
    if (!useSSE || !window.EventSource) {
        console.log('SSE not supported or disabled, falling back to polling');
        startNotificationPolling();
        return;
    }

    // Close existing connection if any
    if (eventSource) {
        eventSource.close();
    }

    // Create new SSE connection
    const baseUrl = window.location.pathname.includes('/pages/') ? '../../api/' : 'api/';
    eventSource = new EventSource(baseUrl + 'realtime_updates.php');

    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    let reconnectDelay = 1000; // Start with 1 second

    // Handle incoming updates
    eventSource.addEventListener('update', function(e) {
        try {
            const data = JSON.parse(e.data);
            console.log('SSE Update received:', data);

            // Reset reconnect attempts on successful update
            reconnectAttempts = 0;
            reconnectDelay = 1000;

            // Update notification badge
            if (data.total_notifications !== undefined) {
                updateNotificationBadge(data.total_notifications);
            }

            // Update dashboard stats if they exist
            updateDashboardStats(data);

            // Update notification panel if open
            if (data.notifications && data.notifications.length > 0) {
                updateNotificationPanel(data.notifications, data.total_notifications);
            }
        } catch (error) {
            console.error('Error parsing SSE data:', error);
        }
    });

    // Handle heartbeat
    eventSource.addEventListener('heartbeat', function(e) {
        console.log('SSE Heartbeat received');
        // Reset reconnect attempts on heartbeat
        reconnectAttempts = 0;
        reconnectDelay = 1000;
    });

    // Handle connection errors with exponential backoff
    eventSource.onerror = function(error) {
        console.error('SSE Error:', error);
        console.log('SSE connection state:', eventSource.readyState);

        if (eventSource.readyState === EventSource.CLOSED) {
            reconnectAttempts++;

            if (reconnectAttempts <= maxReconnectAttempts) {
                console.log(`SSE connection closed, attempting to reconnect (${reconnectAttempts}/${maxReconnectAttempts}) in ${reconnectDelay}ms...`);

                setTimeout(function() {
                    console.log('Attempting SSE reconnection...');
                    initializeSSE();
                }, reconnectDelay);

                // Exponential backoff
                reconnectDelay = Math.min(reconnectDelay * 2, 30000); // Max 30 seconds
            } else {
                console.log('SSE max reconnection attempts reached, falling back to polling');
                useSSE = false;
                startNotificationPolling();
            }
        }
    };

    // Handle connection open
    eventSource.onopen = function() {
        console.log('SSE connection established successfully');
        // Reset reconnect attempts
        reconnectAttempts = 0;
        reconnectDelay = 1000;
    };
}

// Store last known state to detect changes
let lastDashboardState = {};

// Update dashboard statistics in real-time
function updateDashboardStats(data) {
    let hasChanges = false;

    // Helper to check and update
    const checkAndUpdate = (key, selector) => {
        if (data[key] !== undefined) {
            const el = document.querySelector(selector);
            if (el) el.textContent = data[key];

            // Check if value changed
            if (lastDashboardState[key] !== undefined && lastDashboardState[key] !== data[key]) {
                hasChanges = true;
                console.log(`Change detected in ${key}: ${lastDashboardState[key]} -> ${data[key]}`);
            }
            lastDashboardState[key] = data[key];
        }
    };

    checkAndUpdate('pending_proposals', '[data-stat="pending_proposals"]');
    checkAndUpdate('pending_reports', '[data-stat="pending_reports"]');
    checkAndUpdate('revision_proposals', '[data-stat="revision_proposals"]');
    checkAndUpdate('revision_reports', '[data-stat="revision_reports"]');
    
    // If we detected significant changes in stats, dispatch event for dashboard to reload tables
    if (hasChanges) {
        console.log('Dispatching dashboard-data-refresh event');
        window.dispatchEvent(new CustomEvent('dashboard-data-refresh', { 
            detail: { 
                data: data,
                timestamp: new Date().getTime() 
            } 
        }));
    }
}

// Start polling for notifications (fallback) with adaptive polling
function startNotificationPolling() {
    console.log('Starting notification polling (SSE fallback)');

    // Initial fetch
    fetchNotifications();

    // Adaptive polling: more frequent initially, then slower
    let pollInterval = 3000; // Start with 3 seconds
    const maxInterval = 15000; // Max 15 seconds
    const minInterval = 3000; // Min 3 seconds

    function adaptivePoll() {
        fetchNotifications();

        // Gradually increase interval if no changes detected
        if (pollInterval < maxInterval) {
            pollInterval = Math.min(pollInterval + 1000, maxInterval);
        }

        notificationCheckInterval = setTimeout(adaptivePoll, pollInterval);
    }

    // Start adaptive polling
    notificationCheckInterval = setTimeout(adaptivePoll, pollInterval);

    // Reset to faster polling when page becomes visible
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && !useSSE) {
            console.log('Page visible, resetting poll interval');
            pollInterval = minInterval;
        }
    });
}

// Trigger immediate refresh (useful after actions like save/approve)
function refreshNotifications() {
    if (useSSE && eventSource && eventSource.readyState === EventSource.OPEN) {
        console.log('SSE active, waiting for next update...');
        // SSE should send updates automatically, but we can force a refresh if needed
        fetchNotifications();
    } else {
        console.log('Refreshing notifications via polling...');
        fetchNotifications();
    }
}

// Force immediate data refresh and update UI
function forceDataRefresh() {
    console.log('Force refreshing all dashboard data...');

    // Refresh notifications
    refreshNotifications();

    // Also refresh any dashboard stats that might be cached
    if (typeof updateDashboardStats === 'function') {
        // This will be called by SSE updates, but we can trigger it manually too
        fetchNotifications();
    }
}

// Stop polling (useful for cleanup)
function stopNotificationPolling() {
    if (notificationCheckInterval) {
        clearInterval(notificationCheckInterval);
    }
}

// Stop SSE connection
function stopSSE() {
    if (eventSource) {
        console.log('Closing SSE connection');
        eventSource.close();
        eventSource = null;
    }
}

// Handle notification click - clear badge immediately
function onNotificationClick(event) {
    // Clear badge immediately when clicking a notification
    updateNotificationBadge(0);
    
    // Mark as read
    markNotificationsAsRead();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Try SSE first, fallback to polling if not supported
    initializeSSE();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopSSE();
    stopNotificationPolling();
});

// Handle page visibility change (pause when tab is hidden to save resources)
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        console.log('Page hidden, maintaining SSE connection in background');
    } else {
        console.log('Page visible, SSE connection active');
        // Optionally refresh immediately when tab becomes visible
        if (!useSSE) {
            refreshNotifications();
        }
    }
});

