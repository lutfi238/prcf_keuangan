<!DOCTYPE html>
<html>
<head>
    <title>SSE Test - Real-Time Updates</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .connected { background: #d4edda; color: #155724; }
        .disconnected { background: #f8d7da; color: #721c24; }
        .message { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 10px; margin: 10px 0; }
        .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 4px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #2196F3; }
        button { background: #2196F3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #1976D2; }
        .log { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; }
        .log-entry { margin: 2px 0; }
        .log-update { color: #4ec9b0; }
        .log-error { color: #f48771; }
        .log-info { color: #608b4e; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔴 Server-Sent Events (SSE) Test</h1>
        <p>This page tests the real-time notification system.</p>
        
        <div id="status" class="status disconnected">
            ⚪ Status: Disconnected
        </div>
        
        <div>
            <button onclick="connectSSE()">Connect</button>
            <button onclick="disconnectSSE()">Disconnect</button>
            <button onclick="clearLog()">Clear Log</button>
        </div>
        
        <h3>Real-Time Statistics</h3>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value" data-stat="pending_proposals">-</div>
                <div>Pending Proposals</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" data-stat="pending_reports">-</div>
                <div>Pending Reports</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" data-stat="total_notifications">-</div>
                <div>Total Notifications</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="lastUpdate">-</div>
                <div>Last Update</div>
            </div>
        </div>
        
        <h3>Event Log</h3>
        <div class="log" id="logContainer">
            <div class="log-entry log-info">Waiting for connection...</div>
        </div>
    </div>
    
    <script>
        let eventSource = null;
        
        function log(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const entry = document.createElement('div');
            entry.className = 'log-entry log-' + type;
            const timestamp = new Date().toLocaleTimeString();
            entry.textContent = `[${timestamp}] ${message}`;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        function updateStatus(connected) {
            const statusEl = document.getElementById('status');
            if (connected) {
                statusEl.className = 'status connected';
                statusEl.textContent = '🟢 Status: Connected to SSE';
            } else {
                statusEl.className = 'status disconnected';
                statusEl.textContent = '🔴 Status: Disconnected';
            }
        }
        
        function connectSSE() {
            if (eventSource) {
                log('Already connected', 'info');
                return;
            }
            
            log('Connecting to SSE endpoint...', 'info');
            eventSource = new EventSource('../api/realtime_updates.php');
            
            eventSource.onopen = function() {
                log('✓ SSE connection established', 'info');
                updateStatus(true);
            };
            
            eventSource.addEventListener('update', function(e) {
                try {
                    const data = JSON.parse(e.data);
                    log('📊 Update received: ' + JSON.stringify(data), 'update');
                    
                    // Update stats
                    Object.keys(data).forEach(key => {
                        const el = document.querySelector('[data-stat="' + key + '"]');
                        if (el && data[key] !== undefined) {
                            el.textContent = data[key];
                        }
                    });
                    
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
                } catch (error) {
                    log('Error parsing update: ' + error.message, 'error');
                }
            });
            
            eventSource.addEventListener('heartbeat', function(e) {
                const data = JSON.parse(e.data);
                log('💓 Heartbeat: ' + data.timestamp, 'info');
            });
            
            eventSource.addEventListener('error', function(e) {
                log('❌ SSE error occurred', 'error');
                console.error('SSE Error:', e);
            });
            
            eventSource.onerror = function(error) {
                log('⚠️ Connection error, will retry...', 'error');
                updateStatus(false);
            };
        }
        
        function disconnectSSE() {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
                log('Disconnected from SSE', 'info');
                updateStatus(false);
            } else {
                log('Not connected', 'info');
            }
        }
        
        function clearLog() {
            document.getElementById('logContainer').innerHTML = '';
            log('Log cleared', 'info');
        }
        
        // Auto-connect on page load
        window.addEventListener('load', function() {
            log('Page loaded, auto-connecting...', 'info');
            setTimeout(connectSSE, 500);
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            disconnectSSE();
        });
    </script>
</body>
</html>

