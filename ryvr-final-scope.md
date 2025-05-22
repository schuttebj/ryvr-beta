# Ryvr Platform - Final Development Scope
Version 1.0 - May 2024

## Table of Contents
1. [Project Overview](#project-overview)
2. [Project Setup & Configuration](#project-setup--configuration)
3. [Technical Architecture](#technical-architecture)
4. [Core Components](#core-components)
5. [User Roles & Permissions](#user-roles--permissions)
6. [Workflow Engine](#workflow-engine)
7. [Integration Framework](#integration-framework)
8. [Data Management](#data-management)
9. [Security & Authentication](#security--authentication)
10. [Usage & Subscription Model](#usage--subscription-model)
11. [Deployment & Environment](#deployment--environment)
12. [Implementation Plan](#implementation-plan)
13. [Automation Catalog](#automation-catalog)
14. [Technical Specifications](#technical-specifications)
15. [Documentation](#documentation)
16. [Success Criteria](#success-criteria)
17. [Maintenance Plan](#maintenance-plan)
18. [Contact Information](#contact-information)

## Project Overview

### Purpose
Ryvr is an automation layer for small-business marketing, designed to streamline and automate various marketing tasks through a WordPress-based platform. The system connects various marketing tools and services, allowing users to create automated workflows for their marketing needs.

### Target Users
- Small business owners (direct users)
- Marketing agencies (managing multiple clients)
- Internal team (platform administrators)

### Scale & Capacity
- Initial target: 50-60 clients
- UK market focus (no internationalization required initially)
- Designed for scalability with potential future migration to different tech stack

## Project Setup & Configuration

### Repository & Version Control
- Repository: https://github.com/schuttebj/ryvr-beta.git
- Branch Strategy: main branch for production
- Development Approach: Modular, simple code structure
- Naming Convention: Simple, clear names with 'ryvr' prefix

### Development Environment
- WordPress 6.5
- PHP 8.2
- Composer for dependency management
- Local development with staging deployment

### Security Framework
- OAuth2 implementation for API connections
- Encrypted storage for API credentials
- Role-based access control
- API key management per client/user
- Secure credential storage in wp_options
- Rate limiting implementation
- Input validation and sanitization
- XSS and CSRF protection

## Technical Architecture

### Core Technology Stack
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

### Plugin Structure
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

## Core Components

### Connector Interface
```php
interface RyvrConnectorInterface
{
    public function init(array $authMeta): void;
    public function read(string $op, array $params);
    public function write(string $op, array $payload);
    public function listen(string $event, callable $cb): void;
}
```

### Workflow Definition
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

### Sample Workflow: New-Keyword Harvest
```yaml
id: new_keyword_harvest
trigger:
  type: cron
  expr: "0 4 * * SUN"
steps:
  - id: dfs_ideas
    type: read
    connector: dataforseo
    op: google_ads_keywords_for_keywords
    params: { seed_keywords: ["{{settings.seed}}"], location_code: 2840 }
  - id: filter
    type: reason
    connector: openai
    template: |
      From the JSON list {{dfs_ideas.result}}, keep only keywords with
      "cpc" < 1 and "search_volume" > 300. Return JSON array.
  - id: cluster
    type: reason
    connector: openai
    template: |
      Cluster the given keywords into ad-group themes. Return JSON:
      [{"group":"<name>","keywords":[...]},...]
  - id: email
    type: write
    connector: slack
    op: post_message
    template: |
      "*New keyword harvest ready* ({{cluster.result | length}} groups)\n```{{cluster.result | to_json_pretty}}```"
```

## User Roles & Permissions

### Role Hierarchy
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

### Feature Access Control
- Role-based feature restrictions
- Plan-based limitations
- Integration access control
- Usage quota management

## Workflow Engine

### Core Features
- JSON/YAML workflow definitions
- Topological step execution
- Error handling strategies
- Usage tracking
- Logging and monitoring

### Error Handling
- Configurable strategies:
  - abort: Stop execution
  - retry: Attempt with backoff
  - fallback: Alternative path
  - notify: Continue with alert

### Workflow Customization
- Template system
- Prompt customization
- Step modification
- Parameter adjustment

### Policies
Declarative YAML snippets saved per workflow:

```yaml
- match: write.connector == "google_ads"
  rule: ctx.get("predicted_cpa") > 1.2 * ctx.get("target_cpa")
  action: require_approval
```

## Integration Framework

### Core Connectors
1. DataForSEO
2. OpenAI
3. Google Analytics 4
4. WordPress
5. Google Ads
6. Slack

### Additional Connectors (Phase 2+)
- Mailchimp
- HubSpot
- Shopify
- Twilio
- Meta Ads
- LinkedIn Ads
- Google Business
- PageSpeed
- Clarity
- Pinecone
- Zapier

### Child Plugin Architecture
- Client site execution for:
  - Post creation
  - SEO tasks
  - Site data collection
- Two-way communication:
  - Phase 1: Main platform to client sites
  - Phase 2: Task creation on client sites
- Data synchronization
- API-based communication

### Integration Standards
- OAuth2 preferred
- API key fallback
- Rate limiting
- Error handling
- Logging

## Data Management

### Custom Post Types
- Clients
- Tasks
- Workflows
- Integration Settings
- Custom Outputs

### Database Tables
```sql
-- Core Tables
wp_ryvr_workflows
wp_ryvr_tasks
wp_ryvr_runs
wp_ryvr_logs
wp_ryvr_api_keys
wp_ryvr_settings
```

### Field Management
- ACF Pro integration
- Custom field definitions
- Output templates
- Data validation

## Security & Authentication

### Authentication Methods
- OAuth2 (primary)
- API Key (fallback)
- WordPress authentication
- Role-based access

### Data Security
- Encrypted credential storage
- Secure API communication
- Rate limiting
- Access logging
- Input validation
- XSS protection
- CSRF protection
- Audit logging

## Usage & Subscription Model

### Credit System
- Per-task credit cost
- Monthly allocation
- Top-up capability
- Usage tracking

### Subscription Tiers
- Basic
- Professional
- Enterprise
- Custom

### Usage Monitoring
- Credit consumption
- Task execution
- API calls
- Resource utilization

## Deployment & Environment

### Infrastructure
- Vultr hosting
- Ubuntu server
- CyberPanel
- GitHub repository

### Deployment Process
- Pull-based updates
- Staging environment
- Production deployment
- Backup strategy

## Implementation Plan

### Development Priority
1. Basic UI framework
2. Core integrations
3. Workflow engine
4. Testing framework
5. UI styling and refinement

### Phase 1: Foundation (Weeks 1-10)

#### Week 1-2: Plugin Setup & Core Architecture
- [ ] Initialize plugin structure
- [ ] Set up Composer dependencies
- [ ] Create database tables
- [ ] Implement basic plugin activation/deactivation
- [ ] Set up development environment
- [ ] Create basic admin interface structure
- [ ] Implement basic security framework
- [ ] Create API management interface

#### Week 3-4: Connector Framework
- [ ] Implement RyvrConnectorInterface
- [ ] Create base connector class
- [ ] Implement authentication handling
- [ ] Set up error handling framework
- [ ] Create DataForSEO connector
- [ ] Create OpenAI connector
- [ ] Create connector testing framework
- [ ] Implement API key management
- [ ] Create connector configuration UI

#### Week 5-6: Workflow Engine Core
- [ ] Implement workflow definition parser
- [ ] Create workflow execution engine
- [ ] Implement step execution logic
- [ ] Set up Action Scheduler integration
- [ ] Create basic logging system
- [ ] Implement usage tracking
- [ ] Create workflow list interface
- [ ] Implement basic workflow editor
- [ ] Create task execution interface

#### Week 7-8: Basic UI & Tasks
- [ ] Create workflow list table
- [ ] Implement basic JSON editor
- [ ] Create task execution interface
- [ ] Implement first 3 core tasks (T-01, T-02, T-03)
- [ ] Set up basic error reporting
- [ ] Create usage dashboard
- [ ] Implement first workflow (T-01)
- [ ] Set up logging system
- [ ] Create basic reporting

#### Week 9-10: Testing & Refinement
- [ ] Complete remaining core tasks (T-04 through T-09)
- [ ] Implement basic testing framework
- [ ] Create initial documentation
- [ ] Perform security audit
- [ ] Optimize performance
- [ ] UI refinement
- [ ] Prepare for Phase 2

### Phase 2: Enhancement (Weeks 11-22)

#### Week 11-13: React-Flow Integration
- [ ] Set up React development environment
- [ ] Implement React-Flow canvas
- [ ] Create node types for different steps
- [ ] Implement drag-and-drop interface
- [ ] Create workflow validation
- [ ] Implement undo/redo functionality

#### Week 14-16: Write Policies & Approval
- [ ] Create policy definition system
- [ ] Implement approval queue
- [ ] Create notification system
- [ ] Implement policy evaluation engine
- [ ] Create approval workflow UI
- [ ] Set up email notifications

#### Week 17-19: Additional Connectors
- [ ] Implement Google Analytics 4 connector
- [ ] Create Google Ads connector
- [ ] Implement WordPress connector
- [ ] Create Slack connector
- [ ] Add connector configuration UI
- [ ] Implement connector testing

#### Week 20-22: Agency Features
- [ ] Implement multi-client management
- [ ] Create client dashboard
- [ ] Implement white labeling
- [ ] Create agency settings
- [ ] Implement usage reporting
- [ ] Create client management UI

### Phase 3: Advanced Features (Weeks 23+)

#### Week 23-25: Marketplace
- [ ] Create workflow template system
- [ ] Implement template sharing
- [ ] Create marketplace UI
- [ ] Implement template installation
- [ ] Create template versioning
- [ ] Implement template updates

#### Week 26-28: Vector Store & AI
- [ ] Set up vector database
- [ ] Implement embedding generation
- [ ] Create similarity search
- [ ] Implement AI-powered suggestions
- [ ] Create AI training interface
- [ ] Implement AI model management

#### Week 29-31: Advanced Integration
- [ ] Create child plugin framework
- [ ] Implement two-way communication
- [ ] Create API endpoints
- [ ] Implement webhook system
- [ ] Create integration testing
- [ ] Implement rate limiting

#### Week 32+: Polish & Scale
- [ ] Implement advanced monitoring
- [ ] Create performance optimization
- [ ] Implement caching system
- [ ] Create backup system
- [ ] Implement disaster recovery
- [ ] Create scaling documentation

### Task Dependencies
- Connector Framework must be completed before Workflow Engine
- Basic UI must be completed before React-Flow integration
- Write Policies must be completed before Approval Queue
- Vector Store must be completed before AI features
- Child Plugin Framework must be completed before Advanced Integration

### Milestone Definitions
- **Alpha Release**: End of Phase 1
  - Basic functionality working
  - Core tasks operational
  - Manual testing complete

- **Beta Release**: End of Phase 2
  - All planned features implemented
  - Agency features complete
  - Initial user testing

- **Production Release**: End of Phase 3
  - All 25 tasks complete
  - Performance optimized
  - Documentation complete
  - Marketplace operational

## Automation Catalog

### Phase 1 Priority Tasks
1. **T-01: Bid & Budget Sync**
   - Trigger: hourly cron
   - Steps: GA4 → cost calc → decision → GoogleAds write

2. **T-02: New-Keyword Harvest**
   - Trigger: weekly cron
   - Steps: DFS keywords → OpenAI cluster → email report

3. **T-03: Blog Draft & Publish**
   - Trigger: on-demand UI
   - Steps: DFS SERP gap → OpenAI outline+draft → WP post (draft)

4. **T-04: Review Responder**
   - Trigger: webhook (new review)
   - Steps: Google Business read → sentiment → OpenAI reply → Google Business write

5. **T-05: Lead-to-CRM & Drip**
   - Trigger: webhook (form)
   - Steps: HubSpot create → segment → Mailchimp flow → Twilio alert

6. **T-06: Heat-map Anomaly Alert**
   - Trigger: daily cron
   - Steps: Clarity rage-clicks → threshold → screenshot url → Slack post

7. **T-07: Vector Memory Updater**
   - Trigger: webhook (file upload)
   - Steps: File → OpenAI embeddings → Pinecone upsert

8. **T-08: Creative Winner Picker**
   - Trigger: daily cron
   - Steps: Meta/Google ad stats → statistical test → pause/scale → OpenAI request new creatives

9. **T-09: Content-Decay Radar**
   - Trigger: weekly cron
   - Steps: GA4 drop calc → DFS SERP diff → OpenAI refresh outline → WP draft

### Phase 2+ Tasks

10. **T-10: Internal-Link Optimizer**
    - Trigger: weekly cron
    - Steps: On-Page crawl → orphan pages → OpenAI link suggestions → WP update (pending)

11. **T-11: Micro A/B Builder**
    - Trigger: threshold trigger (conv-rate)
    - Steps: GA4 low-CVR pages → OpenAI variant → WP duplicate → GA4 experiment create

12. **T-12: Algorithm-Update Sentinel**
    - Trigger: RSS listener
    - Steps: detect core update news → DFS snapshot ranks → Slack alert

13. **T-13: Influencer Match & Reach**
    - Trigger: monthly cron
    - Steps: Creator DB search → vector similarity vs. brand personas → OpenAI outreach draft → HubSpot sequence

14. **T-14: Dynamic Remarketing Feed**
    - Trigger: nightly cron
    - Steps: Shopify new products → OpenAI titles → Google/META feed push

15. **T-15: Cart-Abandon Recovery Loop**
    - Trigger: webhook
    - Steps: Shopify checkout update → Mailchimp + Twilio sequence → GA4 outcome feedback

16. **T-16: Churn-Risk Predictor**
    - Trigger: daily cron
    - Steps: SaaS DB extract → ML churn score → HubSpot tag → Mailchimp nurture

17. **T-17: Pricing-Competitor Watcher**
    - Trigger: daily cron
    - Steps: Scrape competitor prices → diff check → Merchant API update → Slack summary

18. **T-18: Social Crisis Early-Warn**
    - Trigger: real-time stream
    - Steps: Content Analysis negative spike → OpenAI holding statement → Slack ping

19. **T-19: Schema-Validator & Pusher**
    - Trigger: nightly cron
    - Steps: PageSpeed rich-result test → OpenAI JSON-LD → WP update

20. **T-20: GA4 Tag-Health Scanner**
    - Trigger: weekly cron
    - Steps: GA diagnostics → missing events → GTM proposal JSON → Slack

21. **T-21: Budget-Forecast & Cash-Alert**
    - Trigger: daily cron
    - Steps: Spend trend extrapolation → decision → email CFO / throttle ads

22. **T-22: Marketing-Mix Model Lite**
    - Trigger: monthly cron
    - Steps: Fetch spend+revenue per channel → ridge regression → realloc plan → GoogleAds/Meta writes

23. **T-23: Affiliate-Link Checker**
    - Trigger: weekly cron
    - Steps: Crawl → broken affiliate links → fetch best offer → WP patch

24. **T-24: Event-Webinar Stack**
    - Trigger: webhook (Calendly)
    - Steps: build LP → Meta ads → Mailchimp invites → Twilio reminders

25. **T-25: Accessibility Fix Pass**
    - Trigger: monthly cron
    - Steps: Lighthouse audit → OpenAI ARIA/alt suggestions → GitHub pull request

## Technical Specifications

### API Management
- Centralized API key storage
- Per-client API key management
- API usage tracking
- Rate limit monitoring
- Error logging

### UI Components
- Admin dashboard
- Workflow builder
- Task manager
- API configuration
- Usage monitoring
- Error reporting

## Documentation

### Technical Documentation
- Code documentation (Markdown)
- API documentation
- Security documentation
- Deployment guide
- Testing procedures
- Connector development guide
- Workflow creation guide

### User Documentation
- Getting started guide
- Workflow templates
- Troubleshooting guide
- Best practices
- API configuration guide

## Success Criteria

### Phase 1 Completion
- Basic UI functional
- Core integrations working
- First 9 workflows operational
- Security framework implemented
- Testing environment ready

### Phase 2 Completion
- All planned features implemented
- Agency features complete
- Initial user testing complete
- Documentation updated
- Additional connectors implemented

### Phase 3 Completion
- All 25 tasks complete
- Performance optimized
- Security audit complete
- Documentation complete
- Marketplace operational

## Maintenance Plan

### Regular Updates
- Security patches
- Performance optimization
- Bug fixes
- Feature updates

### Monitoring
- Error tracking
- Performance monitoring
- Usage statistics
- Security monitoring

## Contact Information
- Repository: https://github.com/schuttebj/ryvr-beta.git
- Development Lead: [To be assigned]
- Support Contact: [To be assigned]

---

*This document serves as the final reference for the Ryvr platform development. All implementation decisions should align with this scope. The document will be updated as needed during development.* 