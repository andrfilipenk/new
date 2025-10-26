<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM-ERP-CMS-PIM Framework</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            padding: 60px;
            text-align: center;
        }
        h1 {
            color: #667eea;
            font-size: 3em;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .subtitle {
            color: #666;
            font-size: 1.2em;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        .feature {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .feature h3 {
            color: #667eea;
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        .feature p {
            font-size: 0.9em;
            color: #888;
            margin: 0;
        }
        .status {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-top: 40px;
            font-weight: 600;
            font-size: 1.1em;
        }
        .auth-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .btn-primary {
            background: white;
            color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .modules {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 30px;
        }
        .module {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 CRM-ERP-CMS-PIM Platform</h1>
        <p class="subtitle">A unified business management system built on a custom PHP framework with zero dependencies.</p>
        
        <div class="modules">
            <div class="module">CRM</div>
            <div class="module">ERP</div>
            <div class="module">CMS</div>
            <div class="module">PIM</div>
        </div>
        
        <div class="auth-buttons">
            <a href="/login" class="btn btn-primary">Sign In</a>
            <a href="/register" class="btn btn-secondary">Create Account</a>
        </div>
        
        <div class="features">
            <div class="feature">
                <h3>✅ Router</h3>
                <p>RESTful routing with named routes and parameter binding</p>
            </div>
            <div class="feature">
                <h3>✅ Database</h3>
                <p>Connection manager with query builder</p>
            </div>
            <div class="feature">
                <h3>✅ Security</h3>
                <p>CSRF protection, XSS filtering, Argon2id hashing</p>
            </div>
            <div class="feature">
                <h3>✅ Sessions</h3>
                <p>Secure session management</p>
            </div>
            <div class="feature">
                <h3>✅ Cache</h3>
                <p>File-based caching with TTL support</p>
            </div>
            <div class="feature">
                <h3>✅ Logger</h3>
                <p>Multi-level logging system</p>
            </div>
            <div class="feature">
                <h3>✅ Events</h3>
                <p>Event dispatcher for decoupling</p>
            </div>
            <div class="feature">
                <h3>✅ Helpers</h3>
                <p>Global convenience functions</p>
            </div>
        </div>
        
        <div class="status">
            ✓ Framework Status: Operational | Core Components: 19/79 (24%) Complete
        </div>
    </div>
</body>
</html>
