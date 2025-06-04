/**
 * Ryvr Workflow Builder
 * Visual workflow builder with drag-and-drop functionality
 */

class RyvrWorkflowBuilder {
    constructor(container) {
        this.container = container;
        this.nodes = new Map();
        this.connections = new Map();
        this.selectedNode = null;
        this.draggedElement = null;
        this.connectors = {};
        this.availableModels = {};
        
        this.init();
    }
    
    init() {
        this.setupContainer();
        this.setupEventListeners();
        this.loadConnectors();
        this.loadAvailableModels();
    }
    
    setupContainer() {
        this.container.innerHTML = `
            <div class="ryvr-workflow-builder">
                <div class="ryvr-sidebar">
                    <h3>Tasks</h3>
                    <div class="ryvr-connectors-list"></div>
                </div>
                
                <div class="ryvr-canvas">
                    <div class="ryvr-canvas-grid"></div>
                    <div class="ryvr-nodes-container"></div>
                    <svg class="ryvr-connections-svg">
                        <defs>
                            <marker id="arrowhead" markerWidth="10" markerHeight="7" 
                                    refX="10" refY="3.5" orient="auto">
                                <polygon points="0 0, 10 3.5, 0 7" 
                                         fill="var(--ryvr-accent)" />
                            </marker>
                        </defs>
                    </svg>
                </div>
                
                <div class="ryvr-inspector">
                    <div class="ryvr-inspector-content">
                        <div class="empty-state">
                            <div class="icon">⚙️</div>
                            <h4>Select a task to configure</h4>
                            <p>Click on a task node to view and edit its parameters.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.sidebarElement = this.container.querySelector('.ryvr-sidebar');
        this.canvasElement = this.container.querySelector('.ryvr-canvas');
        this.nodesContainer = this.container.querySelector('.ryvr-nodes-container');
        this.connectionsSpg = this.container.querySelector('.ryvr-connections-svg');
        this.inspectorElement = this.container.querySelector('.ryvr-inspector');
        this.inspectorContent = this.container.querySelector('.ryvr-inspector-content');
    }
    
    setupEventListeners() {
        // Canvas drag and drop
        this.canvasElement.addEventListener('dragover', this.handleDragOver.bind(this));
        this.canvasElement.addEventListener('drop', this.handleDrop.bind(this));
        
        // Node selection
        this.canvasElement.addEventListener('click', this.handleCanvasClick.bind(this));
        
        // Prevent default drag behavior on canvas
        this.canvasElement.addEventListener('dragstart', (e) => e.preventDefault());
    }
    
    async loadConnectors() {
        try {
            console.log('Workflow Builder: Loading connectors...');
            console.log('AJAX URL:', ryvrWorkflowBuilder.ajax_url);
            console.log('Nonce:', ryvrWorkflowBuilder.nonce);
            
            if (!ryvrWorkflowBuilder.nonce) {
                console.error('No nonce available - WordPress localization may have failed');
                return;
            }
            
            const response = await fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_get_connectors',
                    nonce: ryvrWorkflowBuilder.nonce
                })
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                this.connectors = data.data;
                console.log('Loaded connectors:', this.connectors);
                this.renderConnectorsList();
            } else {
                console.error('Failed to load connectors:', data.data || data);
                this.showConnectorError('Failed to load connectors: ' + (data.data || 'Unknown error'));
            }
        } catch (error) {
            console.error('Failed to load connectors:', error);
            this.showConnectorError('Failed to load connectors: ' + error.message);
        }
    }
    
    async loadAvailableModels() {
        try {
            if (!ryvrWorkflowBuilder.nonce) {
                console.error('No nonce available for loading models');
                return;
            }
            
            const response = await fetch(ryvrWorkflowBuilder.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ryvr_get_openai_models',
                    nonce: ryvrWorkflowBuilder.nonce
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (data.success) {
                this.availableModels = data.data;
                console.log('Loaded OpenAI models:', this.availableModels);
            } else {
                console.error('Failed to load OpenAI models:', data.data || 'Unknown error');
            }
        } catch (error) {
            console.error('Failed to load OpenAI models:', error);
        }
    }
    
    renderConnectorsList() {
        const connectorsContainer = this.sidebarElement.querySelector('.ryvr-connectors-list');
        
        // Group connectors by category
        const categories = {};
        Object.values(this.connectors).forEach(connector => {
            const category = connector.metadata?.category || 'other';
            if (!categories[category]) {
                categories[category] = [];
            }
            categories[category].push(connector);
        });
        
        connectorsContainer.innerHTML = '';
        
        Object.entries(categories).forEach(([category, connectors]) => {
            const groupElement = document.createElement('div');
            groupElement.className = 'ryvr-connector-group';
            
            const categoryName = this.formatCategoryName(category);
            groupElement.innerHTML = `<h4>${categoryName}</h4>`;
            
            connectors.forEach(connector => {
                Object.entries(connector.actions || {}).forEach(([actionId, action]) => {
                    const taskCard = this.createTaskCard(connector, actionId, action);
                    groupElement.appendChild(taskCard);
                });
            });
            
            connectorsContainer.appendChild(groupElement);
        });
    }
    
    createTaskCard(connector, actionId, action) {
        const card = document.createElement('div');
        card.className = 'ryvr-task-card';
        card.setAttribute('draggable', 'true');
        card.setAttribute('data-connector', connector.metadata.id);
        card.setAttribute('data-action', actionId);
        
        card.innerHTML = `
            <div class="task-icon" style="background: ${connector.metadata.brand_color || '#666'}"></div>
            <div class="task-title">${action.name}</div>
            <div class="task-description">${action.description}</div>
        `;
        
        card.addEventListener('dragstart', this.handleTaskDragStart.bind(this));
        
        return card;
    }
    
    formatCategoryName(category) {
        return category.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    handleTaskDragStart(e) {
        this.draggedElement = {
            connector: e.target.getAttribute('data-connector'),
            action: e.target.getAttribute('data-action'),
            element: e.target
        };
        
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', ''); // Required for drag to work
    }
    
    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }
    
    handleDrop(e) {
        e.preventDefault();
        
        if (!this.draggedElement) return;
        
        const rect = this.canvasElement.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        this.createNode(this.draggedElement.connector, this.draggedElement.action, x, y);
        this.draggedElement = null;
    }
    
    createNode(connectorId, actionId, x, y) {
        const nodeId = `node-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const connector = this.connectors[connectorId];
        const action = connector.actions[actionId];
        
        const nodeData = {
            id: nodeId,
            connectorId,
            actionId,
            x,
            y,
            parameters: {},
            jsonSchema: null
        };
        
        this.nodes.set(nodeId, nodeData);
        this.renderNode(nodeData);
    }
    
    renderNode(nodeData) {
        const connector = this.connectors[nodeData.connectorId];
        const action = connector.actions[nodeData.actionId];
        
        const nodeElement = document.createElement('div');
        nodeElement.className = 'ryvr-node';
        nodeElement.setAttribute('data-node-id', nodeData.id);
        nodeElement.style.left = `${nodeData.x}px`;
        nodeElement.style.top = `${nodeData.y}px`;
        
        nodeElement.innerHTML = `
            <div class="node-header">
                <div class="node-icon" style="background: ${connector.metadata.brand_color}"></div>
                <div class="node-title">${action.name}</div>
                <div class="node-status"></div>
            </div>
            <div class="node-description">${action.description}</div>
            <div class="ryvr-handle source"></div>
            <div class="ryvr-handle target"></div>
        `;
        
        nodeElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectNode(nodeData.id);
        });
        
        // Make node draggable
        this.makeNodeDraggable(nodeElement, nodeData);
        
        this.nodesContainer.appendChild(nodeElement);
    }
    
    makeNodeDraggable(element, nodeData) {
        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };
        
        element.addEventListener('mousedown', (e) => {
            if (e.target.closest('.ryvr-handle')) return; // Don't drag on handles
            
            isDragging = true;
            const rect = element.getBoundingClientRect();
            dragOffset.x = e.clientX - rect.left;
            dragOffset.y = e.clientY - rect.top;
            
            element.style.zIndex = '1000';
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
        
        const handleMouseMove = (e) => {
            if (!isDragging) return;
            
            const canvasRect = this.canvasElement.getBoundingClientRect();
            const x = e.clientX - canvasRect.left - dragOffset.x;
            const y = e.clientY - canvasRect.top - dragOffset.y;
            
            nodeData.x = Math.max(0, x);
            nodeData.y = Math.max(0, y);
            
            element.style.left = `${nodeData.x}px`;
            element.style.top = `${nodeData.y}px`;
        };
        
        const handleMouseUp = () => {
            isDragging = false;
            element.style.zIndex = '';
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
    }
    
    handleCanvasClick(e) {
        if (e.target === this.canvasElement || e.target.closest('.ryvr-canvas-grid')) {
            this.selectNode(null);
        }
    }
    
    selectNode(nodeId) {
        // Deselect all nodes
        this.nodesContainer.querySelectorAll('.ryvr-node').forEach(node => {
            node.classList.remove('selected');
        });
        
        this.selectedNode = nodeId;
        
        if (nodeId) {
            const nodeElement = this.nodesContainer.querySelector(`[data-node-id="${nodeId}"]`);
            nodeElement.classList.add('selected');
            this.showNodeInspector(nodeId);
        } else {
            this.showEmptyInspector();
        }
    }
    
    showNodeInspector(nodeId) {
        const nodeData = this.nodes.get(nodeId);
        const connector = this.connectors[nodeData.connectorId];
        const action = connector.actions[nodeData.actionId];
        
        this.inspectorContent.innerHTML = `
            <h3>${action.name}</h3>
            <div class="ryvr-inspector-form">
                ${this.renderParameterForm(action, nodeData.parameters)}
                ${this.renderJsonSchemaSection(nodeData)}
            </div>
        `;
        
        this.bindParameterFormEvents(nodeId);
    }
    
    renderParameterForm(action, currentParams) {
        const requiredParams = action.parameters?.required || [];
        const optionalParams = action.parameters?.optional || [];
        
        let html = '';
        
        if (requiredParams.length > 0) {
            html += '<h4>Required Parameters</h4>';
            requiredParams.forEach(param => {
                html += this.renderParameterField(param, currentParams[param], true);
            });
        }
        
        if (optionalParams.length > 0) {
            html += '<h4>Optional Parameters</h4>';
            optionalParams.forEach(param => {
                html += this.renderParameterField(param, currentParams[param], false);
            });
        }
        
        return html;
    }
    
    renderParameterField(paramName, currentValue, required) {
        const fieldId = `param-${paramName}`;
        const value = currentValue || '';
        
        // Special handling for specific parameters
        if (paramName === 'model' && this.availableModels.length) {
            return `
                <div class="ryvr-form-group">
                    <label class="ryvr-form-label" for="${fieldId}">
                        ${this.formatParameterName(paramName)} ${required ? '*' : ''}
                    </label>
                    <select class="ryvr-form-select" id="${fieldId}" data-param="${paramName}">
                        <option value="">Select a model...</option>
                        ${this.availableModels.map(model => 
                            `<option value="${model.id}" ${value === model.id ? 'selected' : ''}>
                                ${model.name} (${model.category})
                            </option>`
                        ).join('')}
                    </select>
                </div>
            `;
        }
        
        // Handle arrays (like messages for OpenAI)
        if (paramName === 'messages') {
            return `
                <div class="ryvr-form-group">
                    <label class="ryvr-form-label" for="${fieldId}">
                        ${this.formatParameterName(paramName)} ${required ? '*' : ''}
                    </label>
                    <textarea class="ryvr-form-textarea" id="${fieldId}" data-param="${paramName}" 
                              placeholder='[{"role": "user", "content": "Your prompt here"}]'>${value}</textarea>
                    <div class="ryvr-form-help">Enter JSON array of message objects</div>
                </div>
            `;
        }
        
        // Default text field
        return `
            <div class="ryvr-form-group">
                <label class="ryvr-form-label" for="${fieldId}">
                    ${this.formatParameterName(paramName)} ${required ? '*' : ''}
                </label>
                <input class="ryvr-form-input" type="text" id="${fieldId}" 
                       data-param="${paramName}" value="${value}">
            </div>
        `;
    }
    
    renderJsonSchemaSection(nodeData) {
        if (nodeData.connectorId !== 'openai') return '';
        
        return `
            <h4>JSON Schema Response Format</h4>
            <div class="ryvr-form-group">
                <label class="ryvr-form-label">
                    Enable Structured Output
                    <input type="checkbox" id="enable-json-schema" 
                           ${nodeData.jsonSchema ? 'checked' : ''}>
                </label>
            </div>
            <div id="json-schema-builder" style="display: ${nodeData.jsonSchema ? 'block' : 'none'}">
                ${this.renderJsonSchemaBuilder(nodeData.jsonSchema)}
            </div>
        `;
    }
    
    renderJsonSchemaBuilder(schema) {
        const properties = schema?.schema?.properties || {};
        
        return `
            <div class="ryvr-schema-builder">
                <h5>Response Properties</h5>
                <div id="schema-properties">
                    ${Object.entries(properties).map(([name, prop]) => 
                        this.renderSchemaProperty(name, prop)
                    ).join('')}
                </div>
                <button type="button" class="ryvr-btn ryvr-btn-secondary ryvr-btn-sm" 
                        onclick="ryvrWorkflowBuilder.addSchemaProperty()">
                    + Add Property
                </button>
            </div>
        `;
    }
    
    renderSchemaProperty(name, property) {
        return `
            <div class="ryvr-schema-property">
                <input type="text" placeholder="Property name" value="${name}">
                <select>
                    <option value="string" ${property.type === 'string' ? 'selected' : ''}>String</option>
                    <option value="number" ${property.type === 'number' ? 'selected' : ''}>Number</option>
                    <option value="boolean" ${property.type === 'boolean' ? 'selected' : ''}>Boolean</option>
                    <option value="array" ${property.type === 'array' ? 'selected' : ''}>Array</option>
                    <option value="object" ${property.type === 'object' ? 'selected' : ''}>Object</option>
                </select>
                <button type="button" class="ryvr-btn ryvr-btn-icon" onclick="this.parentElement.remove()">×</button>
            </div>
        `;
    }
    
    bindParameterFormEvents(nodeId) {
        // Parameter input changes
        this.inspectorContent.querySelectorAll('[data-param]').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateNodeParameter(nodeId, e.target.dataset.param, e.target.value);
            });
        });
        
        // JSON Schema toggle
        const schemaToggle = this.inspectorContent.querySelector('#enable-json-schema');
        if (schemaToggle) {
            schemaToggle.addEventListener('change', (e) => {
                const builder = this.inspectorContent.querySelector('#json-schema-builder');
                builder.style.display = e.target.checked ? 'block' : 'none';
                
                if (e.target.checked && !this.nodes.get(nodeId).jsonSchema) {
                    this.initializeJsonSchema(nodeId);
                }
            });
        }
    }
    
    updateNodeParameter(nodeId, paramName, value) {
        const nodeData = this.nodes.get(nodeId);
        nodeData.parameters[paramName] = value;
    }
    
    initializeJsonSchema(nodeId) {
        const nodeData = this.nodes.get(nodeId);
        nodeData.jsonSchema = {
            name: 'response',
            schema: {
                type: 'object',
                properties: {}
            }
        };
    }
    
    addSchemaProperty() {
        const container = this.inspectorContent.querySelector('#schema-properties');
        const propertyElement = document.createElement('div');
        propertyElement.innerHTML = this.renderSchemaProperty('', { type: 'string' });
        container.appendChild(propertyElement.firstElementChild);
    }
    
    formatParameterName(param) {
        return param.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    showEmptyInspector() {
        this.inspectorContent.innerHTML = `
            <div class="empty-state">
                <div class="icon">⚙️</div>
                <h4>Select a task to configure</h4>
                <p>Click on a task node to view and edit its parameters.</p>
            </div>
        `;
    }
    
    // Export workflow as JSON
    exportWorkflow() {
        const workflow = {
            nodes: Array.from(this.nodes.values()),
            connections: Array.from(this.connections.values())
        };
        
        return JSON.stringify(workflow, null, 2);
    }
    
    // Load workflow from JSON
    loadWorkflow(workflowJson) {
        try {
            const workflow = JSON.parse(workflowJson);
            
            // Clear existing nodes and connections
            this.nodes.clear();
            this.connections.clear();
            this.nodesContainer.innerHTML = '';
            
            // Load nodes
            workflow.nodes.forEach(nodeData => {
                this.nodes.set(nodeData.id, nodeData);
                this.renderNode(nodeData);
            });
            
            // TODO: Load connections
            
        } catch (error) {
            console.error('Failed to load workflow:', error);
        }
    }
    
    showConnectorError(message) {
        const connectorsContainer = this.sidebarElement.querySelector('.ryvr-connectors-list');
        connectorsContainer.innerHTML = `
            <div class="ryvr-error-state">
                <div class="icon">⚠️</div>
                <h4>Error Loading Connectors</h4>
                <p>${message}</p>
                <button class="ryvr-btn ryvr-btn-secondary" onclick="location.reload()">Retry</button>
            </div>
        `;
    }
}

// Global instance and utilities
let ryvrWorkflowBuilderInstance = null;

// Preserve existing ryvrWorkflowBuilder data from WordPress localization
const existingData = window.ryvrWorkflowBuilder || {};

window.ryvrWorkflowBuilder = {
    // Preserve WordPress localized data
    nonce: existingData.nonce || '',
    ajax_url: existingData.ajax_url || '',
    
    init(container) {
        ryvrWorkflowBuilderInstance = new RyvrWorkflowBuilder(container);
        return ryvrWorkflowBuilderInstance;
    },
    
    addSchemaProperty() {
        if (ryvrWorkflowBuilderInstance) {
            ryvrWorkflowBuilderInstance.addSchemaProperty();
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.ryvr-workflow-builder-container');
    if (container) {
        window.ryvrWorkflowBuilder.init(container);
    }
}); 