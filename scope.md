Ryvr v1 – Technical Scope & Implementation Handbook
(WordPress / PHP / JS edition – May 2025)

0 . Purpose
Ryvr is an automation layer for small-business marketing.
This document is the single source of truth for the initial WordPress-based build handed to the development team. It covers:

the core plugin architecture

how connectors and workflows are modelled, stored, and executed

detailed instructions for writing modular, future-proof workflows

the complete catalogue of the 25 launch automations

phased delivery milestones and acceptance criteria

1 . High-level architecture
text
Copy
Edit
 ┌──────────────────────── WordPress Admin UI ────────────────────────┐
 │  Gutenberg + React-Flow  →  Ryvr Builder (workflows & connectors) │
 └────────────────────────────────────────────────────────────────────┘
                 │(JSON/YAML)                    ▲ (metrics, logs)
                 ▼                               │
         wp_ryvr_workflows            wp_ryvr_runs  /  wp_ryvr_logs
                 │                               │
     ┌────────── Engine (PHP) ────────────┐      │
     │  • loads workflow graph            │      │
     │  • resolves triggers (cron/webhook)│      │
     │  • topological step execution      │      │
     │  • Action Scheduler queue & retry  │      │
     └────────────────────────────────────┘      │
                 │                               │
            PSR-18 HTTP Client  (default: **Guzzle 7**)  
                 │
        ┌────────┴────────┐
        ▼                 ▼
 «Connector» classes   OpenAI SDK
   (REST, SDK or DB)  (reasoning steps)
Everything lives in one custom Composer-based plugin (ryvr-core) so it can later be split out or migrated.

2 . Technology stack
Layer	Choice	Reason
CMS / UI	WordPress 6.5	Your expertise; Gutenberg gives React for free.
Language	PHP 8.2 + Composer	Modern type system, attributes, Fibers optional later.
HTTP	Guzzle 7 (PSR-18), switchable	Mature, middleware, async Pool, SDK-friendly.
Jobs	Action Scheduler (WooCommerce component)	Cron replacement with retries & logs.
UI libs	@wordpress/components + React-Flow	Out-of-box admin styles; flow-chart builder.
Data	Custom tables (wp_ryvr_*) with JSON columns	Avoids wp_posts bloat, allows SQL WHERE on JSON.
Secrets	.env + wp_options (encrypted)	Simple now; migrate to AWS Secrets later.
Events	WP hooks → optional Redis stream	Emits ryvr/workflow/step_done for chaining.
Testing	PestPHP + MockHandler (Guzzle)	Fast unit tests, easy HTTP mocking.
CI	GitHub Actions (PHPStan + PHPUnit + ESLint)	Standard practice.

3 . Data model & interfaces
3.1 Connector interface
php
Copy
Edit
interface RyvrConnectorInterface
{
    public function init(array $authMeta): void;              // called once
    public function read(string $op, array $params);          // GET-like
    public function write(string $op, array $payload);        // POST/PUT-like
    public function listen(string $event, callable $cb): void // optional
}
All connectors only depend on Psr\Http\Client\ClientInterface (injected).

Auth metadata is stored encrypted (auth_meta JSON) and passed to init().

Adapters for vendor SDKs (Google Ads, Shopify, etc.) implement the same interface, forwarding calls to the SDK.

3.2 Workflow graph (stored as JSON)
jsonc
Copy
Edit
{
  "id": 42,
  "title": "Content-Decay Radar",
  "trigger": { "type":"cron", "expr":"0 3 * * MON" },
  "on_error":"abort",
  "steps":[
    { "id":"ga_pull", "type":"read",    "connector":"ga4",   "op":"query",
      "params":{"metrics":["sessions"],"dateRange":["-90days","-1day"]}},
    { "id":"detect",  "type":"reason",  "connector":"openai",
      "template":"From {{ga_pull.rows | to_json}} ... return JSON array" },
    { "id":"outline", "type":"reason",  "connector":"openai",
      "foreach":"{{detect.result}}",
      "template":"Write a refresh outline for {{item.pagePath}} ..." },
    { "id":"draft",   "type":"write",   "connector":"wordpress",
      "op":"create_post","policy":"require_approval",
      "template":{"title":"Updated: {{item.pagePath_title}}",
                  "content":"{{outline.result}}","status":"draft"} }
  ],
  "edges":[["ga_pull","detect"],["detect","outline"],["outline","draft"]]
}
3.3 Policies
Declarative YAML snippets saved per workflow:

yaml
Copy
Edit
- match: write.connector == "google_ads"
  rule: ctx.get("predicted_cpa") > 1.2 * ctx.get("target_cpa")
  action: require_approval
Policies run immediately before each write call.

4 . Developer conventions
css
Copy
Edit
ryvr-core/
 ├─ src/
 │   ├─ Connectors/
 │   │   └─ GoogleAdsConnector.php
 │   ├─ Engine/
 │   │   ├─ Context.php
 │   │   └─ Runner.php
 │   ├─ Workflows/
 │   │   └─ (auto-generated YAML templates go here)
 │   └─ RyvrServiceProvider.php
 ├─ assets/
 │   └─ editor.js  (React Flow bundle)
 ├─ ryvr-core.php  (WordPress plugin bootstrap)
 └─ composer.json
One PHP class == one connector

Unit tests live in /tests; always mock external HTTP.

All code typed & inspected by PHPStan level 8.

Use handlebars-lite ({{ }}) for variable interpolation – implemented via ryvr/helpers.php.

5 . Connector creation workflow (60 s overview)
Create PHP class extending RyvrConnectorBase.

Implement read() / write() using injected HTTP client or SDK.

Register the connector in RyvrServiceProvider with slug & logo.

Provide a manifest for UI:

php
Copy
Edit
return [
  'slug'   => 'shopify',
  'label'  => 'Shopify Admin',
  'ops'    => ['read'=>['get_products'],'write'=>['update_product']],
  'auth'   => ['access_token','shop'],
  'logo'   => 'shopify.svg'
];
Connector now appears in the builder’s sidebar.

6 . Workflow authoring guide (for non-devs)
Builder UI (Phase 2) comprises three panes:

Pane	Function
Left “Connectors”	Drag operations (nodes) onto canvas.
Canvas	Arrange nodes; edges auto-topo-sorted.
Right “Inspector”	Edit selected node’s fields: operation, params (JSON), template (prompt/body), policy, foreach path.

Tips for modularity:

One atomic action per step. Fetch > analyse > write should never mix in the same node.

Name outputs (ctx.update({step_id: result})) clearly; later steps reference via {{detect.result}}.

Always output JSON from LLM steps – enforced by system prompt added automatically.

Use foreach fan-out instead of manual loops ("foreach":"{{detect.result}}").

Keep policies outside of templates; attach to write node.

For steps that might explode in volume (e.g., bulk SERP lookups) set batch_size so Runner pools async requests.

7 . Platform / API connectors in Phase 1–2
Connector slug	Category
dataforseo	SEO/PPC data
openai	LLM reasoning
google_ads	Paid search management
ga4	Analytics read
gsc	Search Console read
wordpress	CMS write/read
mailchimp	Email marketing
slack	Comms
hubspot	CRM
shopify	E-commerce
twilio	SMS / WhatsApp
meta_ads	Facebook & Instagram ads
linkedin_ads	LinkedIn ads
google_business	Reviews & posts
pagespeed	Page performance
clarity	Session analytics
pinecone	Vector DB
zapier	Generic webhook bridge
... (room for 20+ more)	

Only dataforseo, openai, ga4, google_ads, wordpress, slack are mandatory in Phase 1.

8 . Launch automation catalogue (25 tasks)
ID	Task name	Trigger	Core step chain
T-01	Bid & Budget Sync	hourly cron	GA4 → cost calc → decision → GoogleAds write
T-02	New-Keyword Harvest	weekly cron	DFS keywords → OpenAI cluster → email report
T-03	Blog Draft & Publish	on-demand UI	DFS SERP gap → OpenAI outline+draft → WP post (draft)
T-04	Review Responder	webhook (new review)	Google Business read → sentiment → OpenAI reply → Google Business write
T-05	Lead-to-CRM & Drip	webhook (form)	HubSpot create → segment → Mailchimp flow → Twilio alert
T-06	Heat-map Anomaly Alert	daily cron	Clarity rage-clicks → threshold → screenshot url → Slack post
T-07	Vector Memory Updater	webhook (file upload)	File → OpenAI embeddings → Pinecone upsert
T-08	Creative Winner Picker	daily cron	Meta/Google ad stats → statistical test → pause/scale → OpenAI request new creatives
T-09	Content-Decay Radar	weekly cron	GA4 drop calc → DFS SERP diff → OpenAI refresh outline → WP draft
T-10	Internal-Link Optimizer	weekly cron	On-Page crawl → orphan pages → OpenAI link suggestions → WP update (pending)
T-11	Micro A/B Builder	threshold trigger (conv-rate)	GA4 low-CVR pages → OpenAI variant → WP duplicate → GA4 experiment create
T-12	Algorithm-Update Sentinel	RSS listener	detect core update news → DFS snapshot ranks → Slack alert
T-13	Influencer Match & Reach	monthly cron	Creator DB search → vector similarity vs. brand personas → OpenAI outreach draft → HubSpot sequence
T-14	Dynamic Remarketing Feed	nightly cron	Shopify new products → OpenAI titles → Google/META feed push
T-15	Cart-Abandon Recovery Loop	webhook	Shopify checkout update → Mailchimp + Twilio sequence → GA4 outcome feedback
T-16	Churn-Risk Predictor	daily cron	SaaS DB extract → ML churn score → HubSpot tag → Mailchimp nurture
T-17	Pricing-Competitor Watcher	daily cron	Scrape competitor prices → diff check → Merchant API update → Slack summary
T-18	Social Crisis Early-Warn	real-time stream	Content Analysis negative spike → OpenAI holding statement → Slack ping
T-19	Schema-Validator & Pusher	nightly cron	PageSpeed rich-result test → OpenAI JSON-LD → WP update
T-20	GA4 Tag-Health Scanner	weekly cron	GA diagnostics → missing events → GTM proposal JSON → Slack
T-21	Budget-Forecast & Cash-Alert	daily cron	Spend trend extrapolation → decision → email CFO / throttle ads
T-22	Marketing-Mix Model Lite	monthly cron	Fetch spend+revenue per channel → ridge regression → realloc plan → GoogleAds/Meta writes
T-23	Affiliate-Link Checker	weekly cron	Crawl → broken affiliate links → fetch best offer → WP patch
T-24	Event-Webinar Stack	webhook (Calendly)	build LP → Meta ads → Mailchimp invites → Twilio reminders
T-25	Accessibility Fix Pass	monthly cron	Lighthouse audit → OpenAI ARIA/alt suggestions → GitHub pull request

Note: tasks marked bold up to T-09 are Phase 1 deliverables.

9 . Representative full workflow definitions
Three workflows are written in full YAML and stored in /src/Workflows/examples/.

9.1 T-02 – New-Keyword Harvest
yaml
Copy
Edit
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
9.2 T-04 – Review Responder (listener)
yaml
Copy
Edit
id: review_responder
trigger:
  type: webhook
  route: "/ryvr/gmb-review"
steps:
  - id: classify
    type: reason
    connector: openai
    template: |
      You are a classifier. Is this review positive, neutral, or negative?
      "{{payload.review_text}}"
      Respond using JSON: {"sentiment":"positive|neutral|negative"}
  - id: draft_reply
    type: reason
    connector: openai
    template: |
      Write a {{classify.result.sentiment}} reply (up to 80 words) to
      this customer: "{{payload.review_text}}"
  - id: post_reply
    type: write
    connector: google_business
    op: reply_review
    template: { review_id: "{{payload.review_id}}", text: "{{draft_reply.result}}" }
    policy: auto_if_positive_else_require_approval
9.3 T-14 – Dynamic Remarketing Feed
yaml
Copy
Edit
id: dynamic_remarketing
trigger: { type: cron, expr: "0 1 * * *" }
steps:
  - id: new_products
    type: read
    connector: shopify
    op: fetch_products
    params: { created_at_min: "{{now - 1d}}" }
  - id: ad_copy
    type: reason
    connector: openai
    foreach: "{{new_products.items}}"
    template: |
      Create a concise 40-char Google Shopping title and 90-char description
      for product: {{item.title}} – {{item.body_html | strip_tags}}.
      Return JSON: {"title":"", "description":""}
  - id: feed_update
    type: write
    connector: google_merchant
    op: batch_update_products
    template: "{{ad_copy.result}}"
(Remaining 22 workflows follow the same pattern – see /examples/ folder.)

10 . Phased delivery schedule
Phase	Sprint span	Key outputs	Acceptance tests
P1 – Data & Drafts	0–10 weeks	Plugin skeleton; connector SDK; builder v1 (JSON); T-01→T-09 in read/reason mode; Slack/email outputs	✔ Workflows store & run via cron
✔ Action Scheduler retries
✔ Logs visible in admin
P2 – Automation & UI	11–22 weeks	Builder v2 (React-Flow); write policies; approval queue; additional connectors (Ads, Mailchimp, Shopify); automate T-01,03,05,08,14	✔ Manager can approve/reject writes
✔ Parallel foreach runs do not time out
✔ Unit-test coverage ≥ 70 %
P3 – Ecosystem & Scale	23 weeks +	Marketplace import/export; vector store; Slack bot; advanced observability; remaining tasks T-15→T-25	✔ Task templates install in one click
✔ Average step latency stats render in dashboard
✔ All 25 tasks runnable

11 . Definition of “done” for each task
Declarative workflow JSON present in repo.

Connector ops referenced exist and are unit-tested with MockHandler.

A sample run in staging completes with green status.

Logs (input + output JSON) stored and viewable.

Policies (if any) trigger correctly (auto or require approval).

12 . Next steps for the dev team
Clone starter repo (git clone git@github.com:ryvr/ryvr-core.git).

composer install & npm run dev – ensure plugin activates in local WP.

Implement DataForSEOConnector & OpenAIConnector first; run T-02 workflow unit-test.

Build minimal workflow list-table & JSON editor; ship in Sprint 1.

Parallel: design React-Flow canvas (Phase 2) but keep behind feature flag.

Hold weekly demo – show one new connector or task running.

Contact
Product & architecture: <project-lead>@ryvr.ai
Devops & CI: devops@ryvr.ai

Please raise PRs early & often. All architecture questions should reference section numbers in this document to keep the discussion concrete.