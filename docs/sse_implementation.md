# Server-Sent Events (SSE) Implementation

## Overview
Real-time notification and dashboard update system using Server-Sent Events.

## How It Works

### Backend (`api/realtime_updates.php`)
- Streams updates every 5 seconds
- Sends notification counts based on user role
- Maintains persistent connection
- Auto-reconnects on failure

### Frontend (`assets/js/realtime_notifications.js`)
- Connects to SSE endpoint on page load
- Updates notification badge in real-time
- Updates dashboard statistics automatically
- Falls back to polling if SSE fails

## Data Attributes
Dashboard elements with `data-stat` attribute will be updated automatically:

```html
<p data-stat="pending_proposals">5</p>
<p data-stat="pending_reports">3</p>
<p data-stat="revision_proposals">2</p>
<p data-stat="revision_reports">1</p>
```

## Testing
1. Open two browser windows (or different browsers)
2. Log in as different users (e.g., PM in one, FM in another)
3. Create a proposal/report in one window
4. Watch the notification badge update in the other window (within 5 seconds)

## Configuration
To disable SSE and use polling instead:
```javascript
// In realtime_notifications.js
let useSSE = false;
```

## Browser Support
- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- IE11: ⚠️ Falls back to polling

## Monitoring
Check browser console for SSE messages:
- "SSE connection established" - Connected successfully
- "SSE Update received" - Data received from server
- "SSE Heartbeat" - Keep-alive signal
- "SSE connection lost" - Connection dropped, will retry

