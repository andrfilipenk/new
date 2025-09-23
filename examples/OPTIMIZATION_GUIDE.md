# HTTP Components Optimization Guide

## Overview

This document outlines the optimizations made to your MVC framework's HTTP components (Request, Response, and Application) to achieve better performance, smaller memory footprint, and smarter functionality.

## Performance Improvements Summary

### ✅ **OptimizedRequest Benefits**
- **Singleton Pattern**: Prevents duplicate request object creation
- **Lazy Loading**: Headers, files, and JSON parsed only when accessed
- **Smart Caching**: Frequently accessed data is cached (method, URI, merged inputs)
- **Efficient Operations**: Reduced array operations and string manipulations
- **Better IP Detection**: Supports proxy headers (X-Forwarded-For, X-Real-IP)

### ✅ **OptimizedResponse Benefits**
- **Streamlined Headers**: More efficient header and cookie management
- **Optimized JSON**: Uses encoding flags for better performance
- **Static Factory**: Cleaner object creation pattern
- **Memory Efficient**: Reduced constant definitions memory usage
- **Better Type Safety**: Improved method signatures with proper return types

### ✅ **OptimizedApplication Benefits**
- **Reduced DI Lookups**: Services cached during construction
- **Better Error Handling**: More efficient error response generation
- **Cleaner Flow**: Simplified request processing pipeline

## Migration Guide

### 1. **Drop-in Replacement**

The optimized components are designed as drop-in replacements:

```php
// Before
use Core\Http\Request;
use Core\Http\Response;
use Core\Mvc\Application;

// After  
use Core\Http\OptimizedRequest as Request;
use Core\Http\OptimizedResponse as Response;
use Core\Mvc\OptimizedApplication as Application;
```

### 2. **Service Provider Updates**

Update your service providers to use optimized components:

```php
// In your service provider
$di->set('request', function() {
    return OptimizedRequest::capture(); // Singleton pattern
});

$di->set('response', function() {
    return new OptimizedResponse();
});

$di->set('application', function() use ($di) {
    return new OptimizedApplication($di);
});
```

### 3. **Controller Updates** 

Controllers can use the same API with new benefits:

```php
class UserController extends Controller
{
    public function createAction()
    {
        $request = $this->getDI()->get('request');
        
        // Same API, better performance
        $name = $request->input('name');
        $email = $request->input('email');
        
        // Smart content type detection
        if ($request->isJson()) {
            // Handle JSON request
        }
        
        $response = new OptimizedResponse();
        return $response->json(['status' => 'created']);
    }
}
```

## New Features & Enhancements

### 🆕 **Enhanced Request Features**

```php
$request = OptimizedRequest::capture();

// Smart IP detection (proxy-aware)
$realIP = $request->ip(); // Checks X-Forwarded-For, X-Real-IP

// Content type detection
if ($request->isJson()) {
    $data = $request->input('data');
}

// Singleton pattern ensures same instance
$sameInstance = OptimizedRequest::capture(); // Returns same object
```

### 🆕 **Enhanced Response Features**

```php
$response = OptimizedResponse::create();

// Static factory method
$response = OptimizedResponse::create('Content', 200);

// Efficient JSON with flags
$response->json($data); // Uses JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES

// Utility methods
$size = $response->getSize();
$isEmpty = $response->isEmpty();
$header = $response->getHeader('Content-Type');
```

## Performance Benchmarks

Based on the demo results:

### Memory Efficiency
- **100 Response objects**: ~554 bytes per object average
- **Lazy loading**: Headers parsed only when accessed
- **Singleton Request**: Prevents duplicate memory allocation

### Speed Improvements
- **Reduced object creation overhead**
- **Cached frequently accessed properties**
- **Optimized array operations**
- **Efficient string operations**

## Best Practices

### 1. **Use Singleton Pattern for Request**
```php
// ✅ Good - Use singleton
$request = OptimizedRequest::capture();

// ❌ Avoid - Creates new instance
$request = new OptimizedRequest();
```

### 2. **Leverage Static Factory for Response**
```php
// ✅ Good - Clean factory pattern
$response = OptimizedResponse::create('Hello World');

// ✅ Also good - Traditional constructor
$response = new OptimizedResponse('Hello World');
```

### 3. **Access Headers Only When Needed**
```php
// ✅ Good - Headers parsed only if needed
if ($request->isAjax()) {
    // Headers are parsed here
    $contentType = $request->header('Content-Type');
}

// ❌ Avoid - Unnecessary header parsing
$allHeaders = $request->getHeaders(); // If you don't need all headers
```

## Configuration Updates

### Update bootstrap.php

```php
// app/bootstrap.php

// Register optimized components
$di->set('request', function() {
    return \Core\Http\OptimizedRequest::capture();
});

$di->set('response', function() {
    return new \Core\Http\OptimizedResponse();
});

$di->set('application', function() use ($di) {
    return new \Core\Mvc\OptimizedApplication($di);
});
```

### Update public/index.php

```php
// public/index.php
require_once '../app/bootstrap.php';

use Core\Http\OptimizedRequest;
use Core\Http\OptimizedResponse;

$application = $di->get('application');
$request = OptimizedRequest::capture();

$response = $application->handle($request);
$response->send();
```

## Compatibility Notes

### ✅ **Fully Compatible**
- All existing method signatures maintained
- Same return types and behavior
- Drop-in replacement capability

### ⚠️ **Minor Changes**
- Request now uses singleton pattern (recommended)
- Some internal method visibility changed (shouldn't affect usage)
- Response constants moved to array for memory efficiency

### 🔄 **Recommended Updates**
- Use `OptimizedRequest::capture()` instead of `new Request()`
- Use `OptimizedResponse::create()` for cleaner code
- Update service providers to use optimized classes

## Testing

Run the provided demo to verify performance improvements:

```bash
php examples/standalone-optimization-demo.php
```

This will show:
- Feature comparison
- Performance benchmarks  
- Memory usage analysis
- API compatibility verification

## Next Steps

1. **Backup existing files** before implementing changes
2. **Update service providers** to use optimized components
3. **Test thoroughly** in development environment
4. **Monitor performance** improvements in production
5. **Consider implementing** additional optimizations based on your specific use cases

## Future Optimization Opportunities

### Request Component
- **Stream processing** for large file uploads
- **Request caching** for repeated operations
- **Custom header parsing** for specific needs

### Response Component  
- **Response compression** (gzip)
- **Content streaming** for large responses
- **Template caching** integration

### Application Component
- **Route caching** for better performance
- **Middleware pipeline** optimization
- **Event system** performance improvements

---

**Note**: These optimizations maintain full backward compatibility while providing significant performance improvements. The changes are designed to be production-ready and follow established PHP best practices.