
/** override mousedown for grid to select checkbox respecting singleSelect */
Ext.override(Ext.grid.CheckboxSelectionModel, {
	handleMouseDown: function(g, rowIndex, e) {
		e.stopEvent();
		if (this.isSelected(rowIndex)) {
			this.deselectRow(rowIndex);
		} else {
			this.selectRow(rowIndex, true);
			this.grid.getView().focusRow(rowIndex);
		}
	}
});


TYPO3.EnetcacheAnalytics.Performance = Ext.extend(Ext.Panel, {
	layout: 'border',
	autoScroll: true,

	testArray: [],
	testCount: 0,
	callback: null,

	initComponent:function() {
		/**
		 * Enabled test expander for result graph and table
		 */
		TYPO3.EnetcacheAnalytics.Performance.testExpander = new Ext.ux.grid.RowPanelExpander({
			id: 'testExpander',
			createExpandingRowPanelItems: function(record, rowIndex) {
				var panelItems = [
					new Ext.TabPanel({
						plain: true,
						activeTab: 0,
						defaults: {
							autoHeight: true
						},
						record: record,
						items:[
							{title: 'Graph', html: record.data.graph},
							{title: 'Table', html: record.data.table}
						]
					})
				];
				return panelItems;
			},

			getRowClass: function(record, rowIndex, p, ds) {
				var cssClass = '';

				p.cols = p.cols - 1;
				var content = this.bodyContent[record.id];
				if (!content && !this.lazyRender) {
					content = this.getBodyContent(record, rowIndex);
				}
				if (content) {
					p.body = content;
				}
				if (record.data.graph.length > 0 || record.data.table.length > 0) {
					cssClass = 'x-grid3-row-expanded';
				} else {
					cssClass = 'x-grid3-row-collapsed';
				}

				return cssClass;
			},

			renderer : function(v, p, record) {
				if (record.data.graph.length > 0 || record.data.table.length > 0) {
					p.cellAttr = 'rowspan="2"';
					var expanderHtml = '<div class="x-grid3-row-expander">&#160;</div>';
				} else {
					var expanderHtml = '';
				}
				return expanderHtml;
			},

			saveState: function(grid, state){
			}
		});

		/**
		 * Display and handle available and selected tests
		 * this is a drap + drop setup
		 */
		this.availableTestsStore = new Ext.data.DirectStore({
			storeId: 'availableTests',
			idProperty: 'name',
			directFn: TYPO3.EnetcacheAnalytics.Analyzer.getNotEnabledTestEntries,
			root: 'data',
			totalProperty: 'length',
			fields: ['name', 'table', 'graph'],
			paramsAsHash: true,
			paramNames: {
				unique_id: 'name'
			},
			listeners: {
				scope: this,
				'load': function(store, records) {
					store.sort('name', 'ASC');
				}
			}
		});
		this.selectedTestsStore = new Ext.data.DirectStore({
			storeId: 'enabledTests',
			idProperty: 'name',
			directFn: TYPO3.EnetcacheAnalytics.Analyzer.getEnabledTestEntries,
			root: 'data',
			totalProperty: 'length',
			fields: ['name', 'table', 'graph'],
			paramsAsHash: true,
			paramNames: {
				unique_id: 'name'
			},
			listeners: {
				scope: this,
				'load': function(store, records) {
					store.sort('name', 'ASC');
					if (store.getCount() == 0) {
						TYPO3.Flashmessage.display(TYPO3.Severity.information, 'Information', 'Please drag a test from "Available tests" section to "Enabled tests" section. Next select a backend to run the tests on and hit the "Run selected tests" button.', 10);
					}
				}
			}
		});
		var availableTestsColumnModel = new Ext.grid.ColumnModel({
			columns: [
				{id: 'name', header: 'Available Tests', dataIndex: 'name'}
			],
			defaults: {
				sortable: true
			}
		});
		var selectedTestsColumnModel = new Ext.grid.ColumnModel({
			columns: [
				TYPO3.EnetcacheAnalytics.Performance.testExpander,
				{id: 'name', header: 'Enabled Tests', dataIndex: 'name'}
			],
			defaults: {
				sortable: true
			}
		});
		TYPO3.EnetcacheAnalytics.Performance.availableTestsGrid = new Ext.grid.GridPanel({
			ddGroup: 'selectedTestsGridDDGroup',
			store: this.availableTestsStore,
			cm: availableTestsColumnModel,
			enableDragDrop: true,
			stripeRows: true,
			autoExpandColumn: 'name',

			listeners: {
				scope: this,
				afterRender: function(grid) {
					var availableTestsGridDropTargetEl =  TYPO3.EnetcacheAnalytics.Performance.availableTestsGrid.getView().scroller.dom;
                    var availableTestsGridDropTarget = new Ext.dd.DropTarget(availableTestsGridDropTargetEl, {
                        ddGroup    : 'availableTestsGridDDGroup',
                        notifyDrop : function(ddSource, e, data) {
							var record =  ddSource.dragData.selections;
							Ext.each(record, ddSource.grid.store.remove, ddSource.grid.store);
							TYPO3.EnetcacheAnalytics.Performance.availableTestsGrid.store.add(record);
							TYPO3.EnetcacheAnalytics.Performance.availableTestsGrid.store.sort('name', 'ASC');
	                        var ucName = 'moduleData.enetcacheanalytics.performance.enabledTests.' + record[0].data.name;
							TYPO3.BackendUserSettings.ExtDirect.set(
								ucName,
								0,
								function(response) {}
							);
							return true
                        }
                    });
				}
			}
		});
		TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid = new Ext.grid.GridPanel({
			id: 'selectedTestsGrid',
			ddGroup: 'availableTestsGridDDGroup',
			store: this.selectedTestsStore,
			cm: selectedTestsColumnModel,
			plugins: [TYPO3.EnetcacheAnalytics.Performance.testExpander],
			enableDragDrop: true,
			stripeRows: true,
			autoExpandColumn: 'name',

			listeners: {
				scope: this,
				afterRender: function(grid) {
					var selectedTestsGridDropTargetEl =  TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid.getView().scroller.dom;
                    var selectedTestsGridDropTarget = new Ext.dd.DropTarget(selectedTestsGridDropTargetEl, {
                        ddGroup: 'selectedTestsGridDDGroup',
                        notifyDrop: function(ddSource, e, data) {
							var record =  ddSource.dragData.selections;
							Ext.each(record, ddSource.grid.store.remove, ddSource.grid.store);
							TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid.store.add(record);
							TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid.store.sort('name', 'ASC');
	                        var ucName = 'moduleData.enetcacheanalytics.performance.enabledTests.' + record[0].data.name;
							TYPO3.BackendUserSettings.ExtDirect.set(
								ucName,
								1,
								function(response) {}
							);
							return true
                        }
                    });
				}
			}
		});

		/**
		 * Display and handle events on available backends
		 */
		this.backendsSelectionModel  = new Ext.grid.CheckboxSelectionModel({
			singleSelect: false,
			header: '',
			dataIndex: 'selected',
			checkOnly: false,
			listeners: {
				rowselect: function(sm, index, record) {
					var name = 'moduleData.enetcacheanalytics.performance.enabledBackends.' + record.data.name;
					TYPO3.BackendUserSettings.ExtDirect.set(
						name,
                        1,
                        function(response) {}
					);
				},
				rowdeselect: function(sm, index, record) {
					var name = 'moduleData.enetcacheanalytics.performance.enabledBackends.' + record.data.name;
						// @TODO: Use unsetKey, but core doesn't handle dotted notation for this method
					TYPO3.BackendUserSettings.ExtDirect.set(
						name,
                        0,
                        function(response) {}
					);
				}
			}
		});
		var backendsGridColumnModel = new Ext.grid.ColumnModel({
			columns: [
				this.backendsSelectionModel,
				{id: 'name', header: 'Name', dataIndex: 'name'}
			],
			defaults: {
				sortable: true
			}
		});
		this.backendsStore = new Ext.data.DirectStore({
			storeId: 'backends',
			idProperty: 'uid',
			directFn: TYPO3.EnetcacheAnalytics.Analyzer.getBackends,
			root: 'data',
			totalProperty: 'length',
			fields: ['selected', 'name'],
			paramsAsHash: true,
			paramNames: {
				unique_id: 'name'
			},
			listeners: {
				scope: this,
				'load': function(store, records) {
						// get selected backends to update selection
					var a = [];
					for (var i=0; i<records.length; i++) {
						if(records[i].data.selected) {
							a.push(records[i]);
						}
					}
					this.backendsSelectionModel.selectRecords(a);
					store.sort('name', 'ASC');
				}
			}
		});
		TYPO3.EnetcacheAnalytics.Performance.backendsGrid = new Ext.grid.GridPanel({
			id: 'backendsGrid',
			store: this.backendsStore,
			cm: backendsGridColumnModel,
			sm: this.backendsSelectionModel,
			autoExpandColumn: 'name',
			autoHeight: true,
			stripeRows: true
		});

		/**
		 * Display and handle events on settings (scaleFactor and dataPoints)
		 */
		TYPO3.EnetcacheAnalytics.Performance.settingsForm = new Ext.form.FormPanel({
			autoHeight: true,
			id: 'parameterForm',
			items: [{
				name: 'dataPoints',
				fieldLabel: 'Number of data points',
				labelStyle: 'font-weight:bold; width: 150px;',
				xtype: 'numberfield',
				allowBlank: true,
				width: 40,
				listeners: {
					change: function(field, newValue, oldValue) {
						TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid.disable();
						TYPO3.BackendUserSettings.ExtDirect.set(
							'moduleData.enetcacheanalytics.performance.settings.dataPoints',
                            newValue,
                            function(response) {
	                            TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid.enable();
                            }
						);
					},
					beforerender: function(field) {
						field.value = TYPO3.settings.enetcacheAnalytics.performance.settings.dataPoints;
					},
					scope: this
				}
			},{
				name: 'scaleFactor',
				fieldLabel: 'Scale factor',
				labelStyle: 'font-weight:bold; width: 150px;',
				xtype: 'numberfield',
				allowBlank: true,
				width: 40,
				listeners: {
					change: function(field, newValue, oldValue) {
						TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid.disable();
						TYPO3.BackendUserSettings.ExtDirect.set(
							'moduleData.enetcacheanalytics.performance.settings.scaleFactor',
                            newValue,
                            function(response) {
	                            TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid.enable();
                            }
						);
					},
					beforerender: function(field) {
						field.value = TYPO3.settings.enetcacheAnalytics.performance.settings.scaleFactor;
					},
					scope: this
				}
			}]
		});

		/**
		 * Progress bar when running tests
		 */
		TYPO3.EnetcacheAnalytics.Performance.actionProgressBar = new Ext.ProgressBar ({
			id:  'actionProgressBar',
			style: 'margin: 5px 2px 0 2px',
			animate: true,
			hidden: true,
			defaults: {
				flex: 1
			}
		});

		/**
		 * Button to run tests
		 */
		TYPO3.EnetcacheAnalytics.Performance.actionPanel = {
			xtype: 'container',
			layout: 'hbox',
			height: 24,
			id: 'actionPanel',
			defaults: {
				flex: true
			},
			items: [{
				xtype: 'button',
				text: 'Run selected tests',
				id: 'performance-runTests',
				margins: '3 0 0 2'
			}]
		};

		/**
		 * Compile performance module
		 */
		Ext.apply(this, {
			items: [{
				region: 'north',
				layout: 'fit',
				border: false,
				height: 30,
				items: [
					TYPO3.EnetcacheAnalytics.Performance.actionPanel,
					TYPO3.EnetcacheAnalytics.Performance.actionProgressBar
				]
			},{
				id: 'settingsPanel',
				region: 'west',
				layout: 'border',
				title: 'Settings',
				border: false,
				frame: true,
				autoScroll: true,
				width: 250,
				collapsible: true,
				items: [{
					region: 'north',
					layout: 'fit',
					height: 60,
					items: TYPO3.EnetcacheAnalytics.Performance.settingsForm
				},{
					region: 'center',
					border: false,
					autoScroll: true,
					items: TYPO3.EnetcacheAnalytics.Performance.backendsGrid
				},{
					region: 'south',
					layout: 'fit',
					height: 250,
					items: TYPO3.EnetcacheAnalytics.Performance.availableTestsGrid
				}]
			},{
				region: 'center',
				layout: 'fit',
				border: false,
				autoScroll: true,

				items: [
					TYPO3.EnetcacheAnalytics.Performance.selectedTestsGrid
				]
			}]
		});

		TYPO3.EnetcacheAnalytics.Performance.superclass.initComponent.apply(this, arguments);
		Ext.getCmp('performance-runTests').handler = this.testsActionHandler.createDelegate(this);

	},

	testsActionHandler: function (button, event) {
		var buttonPanel = Ext.getCmp('actionPanel');
		var progressBar = Ext.getCmp('actionProgressBar');

		buttonPanel.hide();
		progressBar.show();

		Ext.getCmp('settingsPanel').collapse(true);

		if (button.id === 'performance-runTests') {
			this.startRunTests(
				Ext.StoreMgr.get('enabledTests'),
				function() {
					buttonPanel.show();
					progressBar.hide();
				}
			);
		}
	},

	startRunTests: function(store, callback) {
		this.testCount = store.data.items.length;
		this.callback = callback;

		this.testArray = [];
		for (var i = 0; i < this.testCount; i++) {
			this.testArray.push(store.data.items[i].data.name);
		}
			// start process
		this.executeTest();
	},

	executeTest: function(response) {
		var grid = Ext.getCmp('selectedTestsGrid');
		var row = this.testCount - this.testArray.length;
		var record = grid.store.getAt(row);
		var i;

		if (response) {
			var executedTest = grid.store.getAt(row - 1);
			executedTest.set('table', response['table']);
			executedTest.set('graph', response['graph']);
			executedTest.commit();
		}

		if (this.testArray.length > 0) {
			var test = this.testArray.shift();

				// Update Progressbar
			Ext.getCmp('actionProgressBar').updateProgress(
				(row + 1) / this.testCount,
				'Running test: ' + record.id + ' ' + (row+1) + ' of ' + this.testCount
			);

			TYPO3.EnetcacheAnalytics.Analyzer.runPerformanceTest(
				test,
				function(response) {
					this.executeTest(response);
				},
				this
			);
		} else {
			this.callback();
		}
	},

	onRender:function() {
		this.availableTestsStore.load();
		this.selectedTestsStore.load();
		this.backendsStore.load();

		TYPO3.EnetcacheAnalytics.Performance.superclass.onRender.apply(this, arguments);
	}
});

Ext.reg('TYPO3.EnetcacheAnalytics.Performance', TYPO3.EnetcacheAnalytics.Performance);