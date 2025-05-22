# Ryvr Platform - Development Scope Document
Version 1.0 - May 2024

## Table of Contents
1. [Project Overview](#1-project-overview)
2. [Technical Architecture](#2-technical-architecture)
3. [Core Components](#3-core-components)
4. [User Roles & Permissions](#4-user-roles--permissions)
5. [Workflow Engine](#5-workflow-engine)
6. [Integration Framework](#6-integration-framework)
7. [Data Management](#7-data-management)
8. [Security & Authentication](#8-security--authentication)
9. [Usage & Subscription Model](#9-usage--subscription-model)
10. [Deployment & Environment](#10-deployment--environment)
11. [Development Phases](#11-development-phases)
12. [Testing & Quality Assurance](#12-testing--quality-assurance)
13. [Documentation Requirements](#13-documentation-requirements)

## 1. Project Overview

### 1.1 Purpose
Ryvr is an automation layer for small-business marketing, designed to streamline and automate various marketing tasks through a WordPress-based platform. The system connects various marketing tools and services, allowing users to create automated workflows for their marketing needs.

### 1.2 Target Users
- Small business owners (direct users)
- Marketing agencies (managing multiple clients)
- Internal team (platform administrators)

### 1.3 Scale & Capacity
- Initial target: 50-60 clients
- UK market focus (no internationalization required initially)
- Designed for scalability with potential future migration to different tech stack

## 2. Technical Architecture

### 2.1 Core Technology Stack
- **CMS**: WordPress 6.5
- **Language**: PHP 8.2
- **Package Management**: Composer
- **HTTP Client**: Guzzle 7 (PSR-18)
- **Job Queue**: Action Scheduler (WooCommerce component)
- **UI Libraries**: 
  - @wordpress/components
  - React-Flow
- **Database**: Custom tables (wp_ryvr_*) with JSON columns
- **Field Management**: ACF Pro
- **Frontend Builder**: Bricks Builder

### 2.2 Plugin Structure
```
ryvr-core/
├─ src/
│   ├─ Connectors/
│   │   └─ [ConnectorName]Connector.php
│   ├─ Engine/
│   │   ├─ Context.php
│   │   └─ Runner.php
│   ├─ Workflows/
│   │   └─ (YAML templates)
│   └─ RyvrServiceProvider.php
├─ assets/
│   └─ editor.js
├─ ryvr-core.php
└─ composer.json
```

## 3. Core Components

### 3.1 Connector Interface
```php
interface RyvrConnectorInterface
{
    public function init(array $authMeta): void;
    public function read(string $op, array $params);
    public function write(string $op, array $payload);
    public function listen(string $event, callable $cb): void;
}
```

### 3.2 Workflow Definition
```json
{
  "id": "workflow_id",
  "title": "Workflow Name",
  "trigger": { 
    "type": "cron|webhook",
    "expr": "cron_expression"
  },
  "on_error": "abort|retry|fallback|notify",
  "steps": [
    {
      "id": "step_id",
      "type": "read|reason|write",
      "connector": "connector_name",
      "op": "operation_name",
      "params": {},
      "template": "template_string"
    }
  ],
  "edges": [["step1", "step2"]]
}
```

## 4. User Roles & Permissions

### 4.1 Role Hierarchy
1. **Admin**
   - Full system access
   - Platform configuration
   - User management
   - System monitoring

2. **Agency**
   - Multi-client management
   - Workflow customization
   - Client management
   - Usage monitoring

3. **User**
   - Single business management
   - Basic workflow execution
   - Limited customization
   - Usage tracking

### 4.2 Feature Access Control
- Role-based feature restrictions
- Plan-based limitations
- Integration access control
- Usage quota management

## 5. Workflow Engine

### 5.1 Core Features
- JSON/YAML workflow definitions
- Topological step execution
- Error handling strategies
- Usage tracking
- Logging and monitoring

### 5.2 Error Handling
- Configurable strategies:
  - abort: Stop execution
  - retry: Attempt with backoff
  - fallback: Alternative path
  - notify: Continue with alert

### 5.3 Workflow Customization
- Template system
- Prompt customization
- Step modification
- Parameter adjustment

## 6. Integration Framework

### 6.1 Core Connectors
1. DataForSEO
2. OpenAI
3. Google Analytics 4
4. WordPress
5. Google Ads
6. Slack

### 6.2 Child Plugin Architecture
- Client site execution
- Data collection
- Two-way communication
- Task synchronization

### 6.3 Integration Standards
- OAuth2 preferred
- API key fallback
- Rate limiting
- Error handling
- Logging

## 7. Data Management

### 7.1 Custom Post Types
- Clients
- Tasks
- Workflows
- Integration Settings
- Custom Outputs

### 7.2 Field Management
- ACF Pro integration
- Custom field definitions
- Output templates
- Data validation

## 8. Security & Authentication

### 8.1 Authentication Methods
- OAuth2 (primary)
- API Key (fallback)
- WordPress authentication
- Role-based access

### 8.2 Data Security
- Encrypted credential storage
- Secure API communication
- Rate limiting
- Access logging

## 9. Usage & Subscription Model

### 9.1 Credit System
- Per-task credit cost
- Monthly allocation
- Top-up capability
- Usage tracking

### 9.2 Subscription Tiers
- Basic
- Professional
- Enterprise
- Custom

### 9.3 Usage Monitoring
- Credit consumption
- Task execution
- API calls
- Resource utilization

## 10. Deployment & Environment

### 10.1 Infrastructure
- Vultr hosting
- Ubuntu server
- CyberPanel
- GitHub repository

### 10.2 Deployment Process
- Pull-based updates
- Staging environment
- Production deployment
- Backup strategy

## 11. Development Phases

### 11.1 Phase 1 (0-10 weeks)
- Plugin skeleton
- Connector SDK
- Builder v1
- Core tasks (T-01→T-09)

### 11.2 Phase 2 (11-22 weeks)
- React-Flow builder
- Write policies
- Approval queue
- Additional connectors

### 11.3 Phase 3 (23+ weeks)
- Marketplace
- Vector store
- Slack bot
- Advanced tasks

### 11.4 Detailed Development Tasks

#### Phase 1: Foundation (Weeks 1-10)

##### Week 1-2: Plugin Setup & Core Architecture
- [ ] Initialize plugin structure
- [ ] Set up Composer dependencies
- [ ] Create database tables
- [ ] Implement basic plugin activation/deactivation
- [ ] Set up development environment
- [ ] Create basic admin interface structure

##### Week 3-4: Connector Framework
- [ ] Implement RyvrConnectorInterface
- [ ] Create base connector class
- [ ] Implement authentication handling
- [ ] Set up error handling framework
- [ ] Create DataForSEO connector
- [ ] Create OpenAI connector

##### Week 5-6: Workflow Engine Core
- [ ] Implement workflow definition parser
- [ ] Create workflow execution engine
- [ ] Implement step execution logic
- [ ] Set up Action Scheduler integration
- [ ] Create basic logging system
- [ ] Implement usage tracking

##### Week 7-8: Basic UI & Tasks
- [ ] Create workflow list table
- [ ] Implement basic JSON editor
- [ ] Create task execution interface
- [ ] Implement first 3 core tasks
- [ ] Set up basic error reporting
- [ ] Create usage dashboard

##### Week 9-10: Testing & Refinement
- [ ] Complete remaining core tasks
- [ ] Implement basic testing framework
- [ ] Create initial documentation
- [ ] Perform security audit
- [ ] Optimize performance
- [ ] Prepare for Phase 2

#### Phase 2: Enhancement (Weeks 11-22)

##### Week 11-13: React-Flow Integration
- [ ] Set up React development environment
- [ ] Implement React-Flow canvas
- [ ] Create node types for different steps
- [ ] Implement drag-and-drop interface
- [ ] Create workflow validation
- [ ] Implement undo/redo functionality

##### Week 14-16: Write Policies & Approval
- [ ] Create policy definition system
- [ ] Implement approval queue
- [ ] Create notification system
- [ ] Implement policy evaluation engine
- [ ] Create approval workflow UI
- [ ] Set up email notifications

##### Week 17-19: Additional Connectors
- [ ] Implement Google Analytics 4 connector
- [ ] Create Google Ads connector
- [ ] Implement WordPress connector
- [ ] Create Slack connector
- [ ] Add connector configuration UI
- [ ] Implement connector testing

##### Week 20-22: Agency Features
- [ ] Implement multi-client management
- [ ] Create client dashboard
- [ ] Implement white labeling
- [ ] Create agency settings
- [ ] Implement usage reporting
- [ ] Create client management UI

#### Phase 3: Advanced Features (Weeks 23+)

##### Week 23-25: Marketplace
- [ ] Create workflow template system
- [ ] Implement template sharing
- [ ] Create marketplace UI
- [ ] Implement template installation
- [ ] Create template versioning
- [ ] Implement template updates

##### Week 26-28: Vector Store & AI
- [ ] Set up vector database
- [ ] Implement embedding generation
- [ ] Create similarity search
- [ ] Implement AI-powered suggestions
- [ ] Create AI training interface
- [ ] Implement AI model management

##### Week 29-31: Advanced Integration
- [ ] Create child plugin framework
- [ ] Implement two-way communication
- [ ] Create API endpoints
- [ ] Implement webhook system
- [ ] Create integration testing
- [ ] Implement rate limiting

##### Week 32+: Polish & Scale
- [ ] Implement advanced monitoring
- [ ] Create performance optimization
- [ ] Implement caching system
- [ ] Create backup system
- [ ] Implement disaster recovery
- [ ] Create scaling documentation

### 11.5 Task Dependencies
- Connector Framework must be completed before Workflow Engine
- Basic UI must be completed before React-Flow integration
- Write Policies must be completed before Approval Queue
- Vector Store must be completed before AI features
- Child Plugin Framework must be completed before Advanced Integration

### 11.6 Milestone Definitions
- **Alpha Release**: End of Phase 1
  - Basic functionality working
  - Core tasks operational
  - Manual testing complete

- **Beta Release**: End of Phase 2
  - All planned features implemented
  - Agency features complete
  - Initial user testing

- **Production Release**: End of Phase 3
  - All features complete
  - Performance optimized
  - Documentation complete

## 12. Testing & Quality Assurance

### 12.1 Testing Strategy
- Manual testing by product owner
- Unit tests for connectors
- Integration testing
- Performance testing

### 12.2 Quality Metrics
- Code coverage
- Performance benchmarks
- Error rates
- Response times

## 13. Documentation Requirements

### 13.1 Technical Documentation
- API documentation
- Connector development guide
- Workflow creation guide
- Deployment guide

### 13.2 User Documentation
- Getting started guide
- Workflow templates
- Troubleshooting guide
- Best practices

## Implementation Guidelines

### Development Process
1. Regular PR submissions
2. Weekly demos
3. Code review process
4. Documentation updates

### Success Criteria
1. All defined workflows operational
2. Proper error handling
3. Usage tracking functional
4. Agency/client separation
5. Basic white labeling

### Maintenance
1. Regular updates
2. Security patches
3. Performance monitoring
4. Usage analytics

## Contact Information
- Product & Architecture: [Project Lead]
- Development: [Development Lead]
- Support: [Support Contact]

---

*This document serves as the primary reference for the Ryvr platform development. All architecture decisions and implementation details should align with this scope. Regular updates to this document will be made as the project evolves.* 