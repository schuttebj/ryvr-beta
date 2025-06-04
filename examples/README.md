# Ryvr Sample Workflows

This directory contains sample workflows that demonstrate the integration capabilities of Ryvr. These workflows showcase how to combine different connectors (OpenAI, DataForSEO) to create powerful automation pipelines.

## Available Sample Workflows

### 1. Basic SEO Analysis (`basic_seo_workflow`)
**Purpose**: Analyze keywords for a website and generate content suggestions
**Connectors**: DataForSEO + OpenAI
**Use Case**: SEO auditing and content planning

**Workflow Steps**:
1. **Keyword Research** - Extract keywords for a target domain using DataForSEO
2. **Analyze Keywords** - Use OpenAI to analyze keyword data and provide SEO recommendations
3. **Generate Content Plan** - Create a 30-day content plan based on the analysis

### 2. Competitor Analysis (`competitor_analysis`)
**Purpose**: Analyze competitor keywords and backlinks to identify opportunities
**Connectors**: DataForSEO + OpenAI
**Use Case**: Competitive intelligence and gap analysis

**Workflow Steps**:
1. **Competitor Keywords** - Get keywords that competitors rank for
2. **Competitor Backlinks** - Analyze competitor backlink profiles
3. **Gap Analysis** - Use AI to identify keyword gaps and link building opportunities

### 3. Content Optimization (`content_optimization`)
**Purpose**: Optimize existing content for better SEO performance
**Connectors**: DataForSEO + OpenAI
**Use Case**: Content improvement and SERP optimization

**Workflow Steps**:
1. **SERP Analysis** - Analyze search results for target keywords
2. **Content Suggestions** - Generate optimization recommendations
3. **Generate Outline** - Create detailed content outlines

### 4. Keyword Research Automation (`keyword_research_automation`)
**Purpose**: Automatically generate and categorize keyword opportunities
**Connectors**: DataForSEO + OpenAI
**Use Case**: Keyword research automation and ad group creation

**Workflow Steps**:
1. **Seed Keywords** - Expand from seed keywords using DataForSEO
2. **Filter Keywords** - Use AI to filter for commercial intent and volume
3. **Categorize Keywords** - Organize keywords by intent and create ad groups

### 5. Link Building Pipeline (`link_building_pipeline`)
**Purpose**: Identify link building opportunities by analyzing competitor backlinks
**Connectors**: DataForSEO + OpenAI
**Use Case**: Link building strategy and outreach planning

**Workflow Steps**:
1. **Competitor Backlinks** - Get competitor referring domains
2. **Our Backlinks** - Get our current referring domains
3. **Find Opportunities** - Use AI to identify unique link opportunities

## How to Use Sample Workflows

### Method 1: Via WordPress Admin (Recommended)

1. **Access the Builder**
   - Go to WordPress Admin → Ryvr → Builder
   - You'll see the visual workflow builder interface

2. **Load a Template**
   - Click the "📋 Load Template" button
   - Browse the available sample workflows
   - Click "Load Template" on the workflow you want to use

3. **Customize the Workflow**
   - Drag tasks from the sidebar to the canvas
   - Click on tasks to configure parameters in the inspector panel
   - Modify inputs, outputs, and connections as needed

4. **Save and Test**
   - Click "💾 Save Workflow" to store your customized workflow
   - Use the test functionality to validate the workflow

### Method 2: Via Integration Test Script

1. **Run the Test Script**
   ```bash
   php test-integration.php
   ```

2. **Review Test Results**
   - The script will validate all sample workflows
   - Check that connectors are properly loaded
   - Verify workflow validation passes

3. **Examine the Output**
   - Look for ✓ (success) and ✗ (error) indicators
   - Review any error messages for troubleshooting

## Setting Up Connectors

Before using the sample workflows, make sure your connectors are properly configured:

### OpenAI Connector
1. Go to Ryvr → Settings → Connectors
2. Configure your OpenAI API key
3. Select default models (gpt-3.5-turbo, gpt-4, etc.)
4. Test the connection

### DataForSEO Connector  
1. Go to Ryvr → Settings → Connectors
2. Enter your DataForSEO API credentials
3. Choose between live or sandbox environment
4. Test the connection

## Workflow Parameters

Most sample workflows use template variables that you can customize:

### Input Variables
- `{{input.target_keyword}}` - The keyword to analyze
- `{{input.competitor_domain}}` - Competitor website to analyze
- `{{input.our_domain}}` - Your website domain
- `{{input.seed_keyword}}` - Starting keyword for research

### Step Variables
- `{{step_name.result}}` - Output from a previous step
- `{{keyword_research.result}}` - Results from keyword research step
- `{{analyze_keywords.result}}` - Results from keyword analysis

## Customization Guide

### Modifying Parameters
1. **Location and Language**
   - Change `location_code` (2840 = United States)
   - Change `language_code` (en = English)

2. **Limits and Filters**
   - Adjust `limit` parameters for result counts
   - Modify search volume thresholds
   - Change CPC and competition filters

3. **AI Prompts**
   - Customize OpenAI prompts for different outputs
   - Adjust tone, format, and requirements
   - Add specific industry context

### Creating New Workflows
1. **Start with a Template**
   - Load a similar sample workflow
   - Modify steps and parameters
   - Save with a new name

2. **Add New Steps**
   - Drag additional tasks from the sidebar
   - Connect steps with data flow
   - Configure each step's parameters

3. **Test and Iterate**
   - Use small datasets for testing
   - Validate each step's output
   - Refine prompts and parameters

## Troubleshooting

### Common Issues

1. **"Connector not found" Error**
   - Ensure the connector is properly installed
   - Check connector configuration in settings
   - Verify API credentials are correct

2. **"Invalid parameters" Error**
   - Check required parameters are provided
   - Validate parameter formats (arrays, strings, numbers)
   - Review connector documentation for parameter requirements

3. **"Rate limit exceeded" Error**
   - Implement delays between API calls
   - Use smaller batch sizes
   - Check API quota and limits

4. **"Invalid JSON" Error**
   - Validate workflow JSON syntax
   - Check for missing commas or brackets
   - Use the built-in validator

### Getting Help

1. **Debug Mode**
   - Enable debug logging in WordPress
   - Check error logs for detailed information
   - Review API response data

2. **Testing Tools**
   - Use the integration test script
   - Test individual connectors separately
   - Validate workflows before execution

3. **Documentation**
   - Check connector-specific documentation
   - Review API documentation for external services
   - Consult WordPress debug tools

## Next Steps

1. **Explore More Connectors**
   - Add Google Analytics connector
   - Integrate with social media platforms
   - Connect to CRM systems

2. **Advanced Workflows**
   - Add conditional logic (if/then statements)
   - Implement loops and iterations
   - Create multi-branch workflows

3. **Automation**
   - Set up cron triggers for automatic execution
   - Configure webhook triggers for real-time processing
   - Implement error handling and notifications

4. **Monitoring**
   - Set up workflow execution logs
   - Monitor API usage and costs
   - Track workflow performance metrics

## Support

For additional help and support:
- Check the main Ryvr documentation
- Review connector-specific guides
- Test individual components before building complex workflows
- Use the integration test script to validate your setup 