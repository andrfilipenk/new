# Accessing Services via DI

<cite>
**Referenced Files in This Document**   
- [Injectable.php](file://app/Core/Di/Injectable.php) - *Updated in recent commit*
- [Controller.php](file://app/Core/Mvc/Controller.php) - *Updated in recent commit*
- [Dashboard.php](file://app/Module/Base/Controller/Dashboard.php)
- [User.php](file://app/Module/Admin/Controller/User.php)
- [Container.php](file://app/Core/Di/Container.php) - *Updated in recent commit*
- [CrudController.php](file://app/Core/Mvc/CrudController.php) - *Added in recent commit*
- [UserService.php](file://app/Module/Admin/Services/UserService.php)
- [BaseService.php](file://app/Core/Services/BaseService.php)
</cite>

## Update Summary
**Changes Made**   
- Added new section on enterprise-level CRUD controller service integration
- Updated best practices to reflect service layer separation
- Enhanced testing considerations for service-oriented architecture
- Added new diagram for CRUD service flow
- Updated section sources to reflect new and modified files

## Table of Contents
1. [Introduction](#introduction)
2. [The Injectable Trait](#the-injectable-trait)
3. [Built-in Service Shortcuts](#built-in-service-shortcuts)
4. [Accessing Any Registered Service](#accessing-any-registered-service)
5. [View Data Passing with Dashboard.php](#view-data-passing-with-dashboardphp)
6. [Form Handling with Request Data in User.php](#form-handling-with-request-data-in-userphp)
7. [Enterprise CRUD Controller Service Integration](#enterprise-crud-controller-service-integration)
8. [Best Practices for Service Usage](#best-practices-for-service-usage)
9. [Testing Considerations](#testing-considerations)
10. [Common Issues and Solutions](#common-issues-and-solutions)
11. [Conclusion](#conclusion)

## Introduction
This document explains how controllers access framework services through the Dependency Injection (DI) container in the application architecture. It covers the Injectable trait, built-in service shortcuts, and methods for retrieving any registered service. The document uses Dashboard.php to illustrate view data passing and User.php for form handling with request data. With the introduction of the enterprise-level CrudController, this documentation has been updated to reflect the new service integration patterns. Best practices, testing considerations, and common issues are also addressed.

**Section sources**
- [Controller.php](file://app/Core/Mvc/Controller.php#L9-L124)
- [Injectable.php](file://app/Core/Di/Injectable.php#L9-L47)

## The Injectable Trait
The Injectable trait enables classes to access the DI container through the getDI() method. When a class uses this trait, it can retrieve services from the container without needing to pass dependencies explicitly. The trait provides a setDI() method to inject the container and a getDI() method to retrieve it. If no container is set, getDI() falls back to the default container instance.

```mermaid
classDiagram
class Injectable {
+setDI(ContainerInterface $di) void
+getDI() ContainerInterface
+__get(string $property) mixed
+__isset(string $property) bool
}
class Controller {
+getView() View
+getRequest() Request
+flashSuccess($message) void
+flashError($message) void
}
Controller ..> Injectable : uses
```

**Diagram sources**
- [Injectable.php](file://app/Core/Di/Injectable.php#L9-L47)
- [Controller.php](file://app/Core/Mvc/Controller.php#L9-L124)

**Section sources**
- [Injectable.php](file://app/Core/Di/Injectable.php#L9-L47)

## Built-in Service Shortcuts
Controllers provide convenient shortcut methods to access commonly used services without directly calling the DI container. These include getRequest() for HTTP request data, getView() for rendering views, and flashSuccess()/flashError() for session-based messaging. These methods abstract the DI container access, making controller code cleaner and more readable.

```mermaid
sequenceDiagram
participant Controller
participant DI as DI Container
participant Request
participant Session
Controller->>DI : get('request')
DI-->>Controller : Request instance
Controller->>DI : get('session')
DI-->>Controller : Session instance
Controller->>Session : flash('success', message)
```

**Diagram sources**
- [Controller.php](file://app/Core/Mvc/Controller.php#L25-L37)
- [Controller.php](file://app/Core/Mvc/Controller.php#L65-L70)
- [Controller.php](file://app/Core/Mvc/Controller.php#L115-L122)

**Section sources**
- [Controller.php](file://app/Core/Mvc/Controller.php#L25-L70)
- [Controller.php](file://app/Core/Mvc/Controller.php#L115-L122)

## Accessing Any Registered Service
Any service registered in the DI container can be accessed using $this->getDI()->get('service_name'). This pattern allows controllers to retrieve database connections, event managers, custom services, or any other registered component. The container resolves dependencies automatically, supporting both class-based and closure-based service definitions.

```mermaid
flowchart TD
A[Controller] --> B[getDI()]
B --> C{Service Registered?}
C --> |Yes| D[Return Service Instance]
C --> |No| E[Throw NotFound Exception]
D --> F[Use Service in Controller]
```

**Diagram sources**
- [Container.php](file://app/Core/Di/Container.php#L60-L75)
- [Injectable.php](file://app/Core/Di/Injectable.php#L15-L25)

**Section sources**
- [Container.php](file://app/Core/Di/Container.php#L60-L75)
- [Injectable.php](file://app/Core/Di/Injectable.php#L15-L25)

## View Data Passing with Dashboard.php
The Dashboard.php controller demonstrates how to pass data to views using the render() method. It prepares navigation items, statistics, and user information, then passes them as an associative array to the view. The framework automatically handles rendering the appropriate template with the provided data.

```mermaid
sequenceDiagram
participant Dashboard
participant View
participant Template
Dashboard->>Dashboard : Prepare data array
Dashboard->>View : render(null, $data)
View->>Template : Process template with data
Template-->>View : Rendered HTML
View-->>Dashboard : Return rendered content
```

**Diagram sources**
- [Dashboard.php](file://app/Module/Base/Controller/Dashboard.php#L10-L37)
- [Controller.php](file://app/Core/Mvc/Controller.php#L85-L105)

**Section sources**
- [Dashboard.php](file://app/Module/Base/Controller/Dashboard.php#L10-L37)

## Form Handling with Request Data in User.php
The User.php controller shows form handling using request data. It checks for POST requests with isPost(), retrieves form data via getRequest()->all(), and processes user creation and updates. The controller also demonstrates manual session flashing for success/error messages and form value retention on validation failure.

```mermaid
flowchart TD
A[User Controller] --> B{isPost?}
B --> |No| C[Show Form]
B --> |Yes| D[Get Request Data]
D --> E[Process Data]
E --> F{Save Successful?}
F --> |Yes| G[flashSuccess + Redirect]
F --> |No| H[flashError + Show Form with Data]
```

**Diagram sources**
- [User.php](file://app/Module/Admin/Controller/User.php#L15-L81)
- [Controller.php](file://app/Core/Mvc/Controller.php#L45-L55)

**Section sources**
- [User.php](file://app/Module/Admin/Controller/User.php#L15-L81)

## Enterprise CRUD Controller Service Integration
The new CrudController implements enterprise-level service integration patterns. When a service class is defined in the serviceClass property, CRUD operations are delegated to the service layer through the DI container. This separation of concerns allows business logic to be maintained independently of controller responsibilities.

```mermaid
sequenceDiagram
participant Controller
participant DI as DI Container
participant Service
participant Model
Controller->>Controller : getValidatedData()
Controller->>DI : get(serviceClass)
DI-->>Controller : Service instance
Controller->>Service : create/update/delete(data)
Service->>Model : Execute business logic
Model-->>Service : Return result
Service-->>Controller : Return processed record
Controller->>Controller : Handle response
```

**Diagram sources**
- [CrudController.php](file://app/Core/Mvc/CrudController.php#L245-L291)
- [UserService.php](file://app/Module/Admin/Services/UserService.php#L20-L50)

**Section sources**
- [CrudController.php](file://app/Core/Mvc/CrudController.php#L245-L291)
- [UserService.php](file://app/Module/Admin/Services/UserService.php#L20-L50)

## Best Practices for Service Usage
When using services through the DI container, avoid tight coupling by depending on interfaces rather than concrete implementations. Use the built-in shortcuts when available, and always check for service existence with has() before retrieval when optional. Register services with meaningful names and avoid direct container access in business logic classes when possible. With the introduction of the CrudController, business logic should be moved to service classes that are injected via the DI container, keeping controllers focused on request/response handling.

**Section sources**
- [Controller.php](file://app/Core/Mvc/Controller.php#L9-L124)
- [Injectable.php](file://app/Core/Di/Injectable.php#L9-L47)
- [Container.php](file://app/Core/Di/Container.php#L45-L55)
- [CrudController.php](file://app/Core/Mvc/CrudController.php#L245-L291)

## Testing Considerations
For testing controllers that use DI services, mock the container and inject it using setDI(). This allows testing controller logic without requiring the actual services. Test both successful service retrieval and error cases (service not found). Verify that shortcut methods properly delegate to the container. When testing CRUD controllers, ensure that service layer interactions are properly mocked and that the DI container returns the expected service instances for create, update, and delete operations.

**Section sources**
- [Injectable.php](file://app/Core/Di/Injectable.php#L11-L13)
- [Controller.php](file://app/Core/Mvc/Controller.php#L9-L124)
- [CrudController.php](file://app/Core/Mvc/CrudController.php#L245-L291)

## Common Issues and Solutions
Common issues include service not found exceptions (check registration and spelling), improper DI container usage (avoid global access), and circular dependencies. Ensure services are registered before use, use dependency injection in constructors when possible, and avoid storing container references in long-lived objects. With the new CrudController implementation, ensure that service classes are properly registered in the DI container and that the serviceClass property is correctly defined in the controller.

**Section sources**
- [Container.php](file://app/Core/Di/Container.php#L70-L75)
- [Injectable.php](file://app/Core/Di/Injectable.php#L30-L40)
- [CrudController.php](file://app/Core/Mvc/CrudController.php#L245-L291)

## Conclusion
The DI container provides a powerful mechanism for accessing framework services in controllers. The Injectable trait enables getDI() access, while built-in shortcuts simplify common operations. Controllers can retrieve any registered service, pass data to views, and handle form submissions effectively. With the introduction of the enterprise-level CrudController, service integration has become a core pattern, promoting separation of concerns and code reuse. Following best practices ensures maintainable, testable code with proper separation of concerns.