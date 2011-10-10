/**
 * Copyright (c) 2008- Samuli J�rvel�
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
 * this entire header must remain intact.
 */

function MollifyDownloadsView() {
	var that = this;
	this.pageUrl = "downloads.html";
	this.onLoadView = onLoadView;
	this.users = null;
	this.usersById = {}
	
	function onLoadView() {
		if (!getSession().features["event_logging"]) {
			onError("Event logging not enabled");
			return;
		}
		$("#downloads-user-text").hide();
		$("#downloads-pager-controls").hide();
		
		$("#button-search").click(that.onSearch);
		$("#downloads-user").change(that.onUserChanged);
				
		$("#downloads-range-start").datepicker();
		$("#downloads-range-end").datepicker();
		
		$("#downloaded-files-list").jqGrid({        
			datatype: "local",
			multiselect: false,
			autowidth: true,
			height: '100%',
		   	colNames:['File'],
		   	colModel:[
				{name:'item',index:'item',width:150, sortable:true}
		   	],
		   	sortname:'item',
		   	sortorder:'asc',
			onSelectRow: function(id){
				that.onFileSelectionChanged();
			}
		});
		
		$("#downloads-list").jqGrid({        
			datatype: "local",
			multiselect: false,
			autowidth: true,
			height: '100%',
		   	colNames:['User', 'Time'],
		   	colModel:[
				{name:'user',index:'user',width:300, sortable:true},
		   		{name:'time',index:'time', width:200, sortable:true, formatter:timeFormatter},
		   	],
		   	sortname:'time',
		   	sortorder:'asc',
		});
		
		var s = getSettings();
		that.showNotDownloaded = (!s || !s.downloads) ? false : s.downloads['show-not-downloaded'];
		
		if (that.showNotDownloaded) {
			$("#users-not-downloaded-section").show();
			
			$("#users-not-downloaded-list").jqGrid({        
				datatype: "local",
				multiselect: false,
				autowidth: true,
				height: '100%',
			   	colNames:['User'],
			   	colModel:[
					{name:'name',index:'name',width:300, sortable:true},
			   	],
			   	sortname:'user',
			   	sortorder:'asc',
			});
		} else {
			$("#users-not-downloaded-section").hide();
			$("#users-downloaded-section > .toolbar").html('').addClass("empty-toolbar")
			$("#users-downloaded-section").width("100%");
		}
		
		that.onFileSelectionChanged();
		getUsers(that.refreshUsers, onServerError);
	}
	
	this.refreshUsers = function(users) {
		that.users = users;
		that.usersById = {}
		
		for (var i=0; i < users.length; i++) {
			var user = users[i];
			that.usersById[user.id] = user;
		}
	}
	
	function timeFormatter(time, options, obj) {
		return formatDateTime(time);
	}
		
	this.onSearch = function() {
		var start = $("#downloads-range-start").val();
		if (start.length > 0) {
			try {
				start = parseDate(start);
			} catch (e) {
				alert("Invalid start date");
				return;
			}
		} else {
			start = null;
		}
		
		var end = $("#downloads-range-end").val();
		if (end.length > 0) {
			try {
				end = parseDate(end);
			} catch (e) {
				alert("Invalid end date");
				return;
			}
		} else {
			end = null;
		}
		
		if (start && end && start > end) {
			alert("Start date cannot be after end date");
			return;
		}
		
		var item = $("#downloads-item-text").val();
		if (!item || item.length == 0) item = null

		that.lastSearch = {start:start, end:end};
		getDownloads(start, end, item, that.onRefreshDownloads, onServerError);
	}
	
	this.onRefreshDownloads = function(files) {
		that.files = files;
		
		var grid = $("#downloaded-files-list");
		grid.jqGrid('clearGridData');

		for(var i=0;i < files.length;i++) {
			var file = files[i];			
			grid.jqGrid('addRowData', i, file);
		}

		that.onFileSelectionChanged();
	}
	
	this.inArray = function(a, o) {
		for (var i=0; i < a.length; i++)
			if (a[i] == o) return true;
		return false;
	}
	
	this.getSelectedFile = function() {
		return $("#downloaded-files-list").getGridParam("selrow");
	}
	
	this.onFileSelectionChanged = function() {
		var file = that.getSelectedFile();
		var selected = (file != null);
		file = selected ? that.files[file].item : null;
				
		$("#downloads-list").jqGrid('clearGridData');
		$("#users-not-downloaded-list").jqGrid('clearGridData');
		
		if (!selected) {
			$("#download-details-data").hide();
			
			if (!that.files)
				$("#download-details-info").html('<div class="message">Enter search criteria and click "Search"</div>');
			else if (that.files.length == 0)
				$("#download-details-info").html('<div class="message">No downloads</div>');
			else
				$("#download-details-info").html('<div class="message">Select file from the list to view details</div>');
		} else {
			$("#download-details-info").html("<h1>"+file+"</h1>");
			getDownloadEvents(that.lastSearch.start, that.lastSearch.end, file, that.onRefreshDetails, onServerError);
		}
	}
	
	this.onRefreshDetails = function(events) {
		var grid = $("#downloads-list");
		var downloaded = [];
		
		for(var i=0;i < events.length;i++) {
			var event = events[i];
			event.time = parseInternalTime(event.time);
			if (!that.inArray(downloaded, event.user)) downloaded.push(event.user);
			grid.jqGrid('addRowData', event.id, event);
		}
		
		if (that.showNotDownloaded) {
			var grid = $("#users-not-downloaded-list");
			for(var i=0;i < that.users.length;i++) {
				var user = that.users[i];
				if (!that.inArray(downloaded, user.name))
					grid.jqGrid('addRowData', user.id, user);
			}
		}
		
		$("#download-details-data").show();
	}
}

function getDownloads(start, end, file, success, fail) {
	var data = {}
	if (start) data["start_time"] = formatInternalTime(start);
	if (end) data["end_time"] = formatInternalTime(end);
	if (file) data["file"] = item;
	
	request("POST", 'events/downloads', success, fail, JSON.stringify(data));
}

function getDownloadEvents(start, end, file, success, fail) {
	var data = {}
	if (start) data["start_time"] = formatInternalTime(start);
	if (end) data["end_time"] = formatInternalTime(end);
	data["file"] = file;
	
	request("POST", 'events/downloads/events', success, fail, JSON.stringify(data));
}