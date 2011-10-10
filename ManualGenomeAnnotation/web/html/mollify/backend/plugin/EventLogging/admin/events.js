/**
 * Copyright (c) 2008- Samuli Järvelä
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
 * this entire header must remain intact.
 */

function MollifyEventsView() {
	var that = this;
	this.pageUrl = "events.html";
	this.onLoadView = onLoadView;
	this.users = null;
	
	function onLoadView() {
		if (!getSession().features["event_logging"]) {
			onError("Feature not available");
			return;
		}
		$("#event-user-text").hide();
		$("#event-type-text").hide();
		$("#events-pager-controls").hide();
		$("#event-details-data").hide();
		
		$("#button-search").click(that.onSearch);
		$("#event-user").change(that.onUserChanged);
		$("#event-type").change(that.onTypeChanged);
		
		$("#events-pager-prev").click(that.onSearchPrev);
		$("#events-pager-next").click(that.onSearchNext);
		
		$("#event-range-start").datepicker();
		$("#event-range-end").datepicker();
		
		$("#events-list").jqGrid({        
			datatype: "local",
			multiselect: false,
			autowidth: true,
			height: '100%',
		   	colNames:['ID', 'Time', 'User', 'Type', 'Item'],
		   	colModel:[
			   	{name:'id',index:'id', width:60, sortable:true, sorttype:"int"},
		   		{name:'time',index:'time', width:150, sortable:true, formatter:timeFormatter},
				{name:'user',index:'user',width:150, sortable:true, formatter:notNullFormatter},
				{name:'type',index:'type',width:150, sortable:true, formatter:typeFormatter},
				{name:'item',index:'item',width:250, sortable:true, formatter:notNullFormatter}
		   	],
		   	sortname:'time',
		   	sortorder:'desc',
			onSelectRow: function(id){
				that.onEventSelectionChanged();
			}
		});
		
		that.onEventSelectionChanged();
		getUsers(that.refreshUsers, onServerError);
	}
	
	this.refreshUsers = function(users) {
		that.users = users;
		that.usersById = {}
		
		for (var i=0; i < users.length; i++) {
			var user = users[i];
			that.usersById[user.id] = user;
		}
		
		that.refreshUserOptions();
		getEventTypes(that.refreshTypes, onServerError);
	}

	this.refreshTypes = function(types) {
		that.types = types;
		that.refreshTypeOptions();
	}
	
	this.refreshUserOptions = function() {
		var options = '<option value="_any">Any</option>';
		options += '<option value="_custom">Custom</option>';
		options += '<option value="-">----------</option>';

		for (var i=0; i < that.users.length; i++) {
			options += '<option value="' + that.users[i].id +'">' + that.users[i].name + '</option>';
		}
		
		$("#event-user").html(options);
	}
	
	this.refreshTypeOptions = function() {
		var options = '<option value="_any">Any</option>';
		options += '<option value="_custom">Custom</option>';
		options += '<option value="-">----------</option>';

		for (var t in that.types)
			options += '<option value="' + t +'">' + that.types[t] + ' (' + t + ')</option>';
		
		$("#event-type").html(options);
	}
	
	this.onUserChanged = function() {
		var sel = $("#event-user").val();
		
		if (sel == '_custom') {
			$("#event-user-text").show();
			$("#event-user-text").text("");
		} else {
			$("#event-user-text").hide();
		}
	}

	this.onTypeChanged = function() {
		var sel = $("#event-type").val();
		
		if (sel == '_custom') {
			$("#event-type-text").show();
			$("#event-type-text").text("");
		} else {
			$("#event-type-text").hide();
		}
	}
	
	this.getSelectedEvent = function() {
		return $("#events-list").getGridParam("selrow");
	}
	
	this.onEventSelectionChanged = function() {
		var event = that.getSelectedEvent();
		var selected = (event != null);
		if (selected) event = that.events[event];
						
		if (!selected) {
			if (!that.events || that.events.length == 0)
				$("#event-details-info").html('<div class="message">Enter search criteria and click "Search"</div>');
			else
				$("#event-details-info").html('<div class="message">Select an event from the list to view details</div>');
		} else {
			$("#event-details-info").html("<h1>Event '"+event.id+"'</h1>");				
			$("#event-details-data").html(that.getEventDetails(event));
		}
		
		if (!selected) {
			$("#event-details-data").hide();
		} else {
			$("#event-details-data").show();
		}
	}
	
	that.getEventDetails = function(event) {
		var html = '<div class="event-info">';
		html += that.getEventDetailsRow("Type:",typeFormatter(event.type));
		html += that.getEventDetailsRow("Time:",timeFormatter(event.time));
		if (event.user != null && event.user.length > 0) html += that.getEventDetailsRow("User:",event.user);
		if (event.item != null && event.item.length > 0) html += that.getEventDetailsRow("Item:",event.item);
		if (event.details == null) {
			html += '<div class="event-info-details"><i>No details</i></div>';
		} else {
			var s = event.details.split(";");
			for (var i=0; i < s.length; i++) {
				var r = s[i].split("=");
				var title = r[0].substr(0, 1).toUpperCase() + r[0].substr(1) + ":";
				html += that.getEventDetailsRow(title,r[1],true);
			}
		}
		html += '</div>';
		return html;
	}
	
	that.getEventDetailsRow = function(title, value, detail) {
		return "<div class='" + (detail ? "event-info-detail-row" : "event-info-row") + "'><div class='event-info-title'>" + title + "</div><div class='event-info-value'>" + value + "</div></div>";
	}
		
	function timeFormatter(time, options, obj) {
		return formatDateTime(time);
	}
	
	function notNullFormatter(o, options, obj) {
		if (o == null) return '';
		return o;
	}

	function typeFormatter(type, options, obj) {
		var t = that.types[type];
		if (!t) return "Unknown ("+type+")";
		return t;
	}
		
	this.onSearch = function(startRow) {
		var start = $("#event-range-start").val();
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
		
		var end = $("#event-range-end").val();
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
		
		var user = $("#event-user").val();
		if (user == '_custom') {
			user = $("#event-user-text").val();
			if (!user || user.length == 0)
				user = null;
		} else if (user != '_any' && user != '-') {
			user = that.usersById[user].name;
		} else {
			user = null;
		}
	
		var type = $("#event-type").val();
		if (type == '_custom') {
			type = $("#event-type-text").val();
			if (!type || type.length == 0)
				type = null;
		} else if (type == '_any' || type == '-') {
			type = null;
		}
			
		var item = $("#event-item-text").val();
		if (!item || item.length == 0) item = null

		getEvents(start, end, user, item, type, startRow, null, that.onRefreshEvents, onServerError);
	}
	
	this.onRefreshEvents = function(result) {
		that.lastSearch = result;
		that.events = {};

		var grid = $("#events-list");
		grid.jqGrid('clearGridData');
		
		for(var i=0;i < result.events.length;i++) {
			var event = result.events[i];			
			event.time = parseInternalTime(event.time);
			
			that.events[event.id] = event;
			grid.jqGrid('addRowData', event.id, event);
		}

		if (result.total > result.count)
			$("#events-pager-controls").show();
		else
			$("#events-pager-controls").hide();
		
		if (result.count > 0) {
			var first = result.start + 1;
			var last = result.start + result.count;
			$("#events-pager-info").html($.template("<div class='info'>Displaying ${first}-${last}/${count}</div>"), {first: first, last: last, count: result.total});
		} else {
			$("#events-pager-info").html("<div class='info'>No events</div>");
		}

		enableButton("events-pager-prev", first > 1);
		enableButton("events-pager-next", result.total > last);
		that.onEventSelectionChanged();
	}
	
	this.onSearchPrev = function() {
		if (!that.lastSearch || that.lastSearch.start <= 0) return;
		that.onSearch(that.lastSearch.start - that.lastSearch.count);
	}
	
	this.onSearchNext = function() {
		if (!that.lastSearch) return;
		
		var last = that.lastSearch.start + that.lastSearch.count;
		if (last >= that.lastSearch.total) return;
		
		that.onSearch(last);
	}
}

function getEventTypes(success, fail) {
	request("POST", 'events/types', success, fail);
}

function getEvents(rangeStart, rangeEnd, user, item, type, start, maxRows, success, fail) {
	var data = {}
	if (rangeStart) data["start_time"] = formatInternalTime(rangeStart);
	if (rangeEnd) data["end_time"] = formatInternalTime(rangeEnd);
	if (start && start >= 0) data["start"] = start;
	if (maxRows) data["max_rows"] = maxRows;
	if (user) data["user"] = user;
	if (item) data["item"] = item;
	if (type) data["type"] = type;
	
	request("POST", 'events/query', success, fail, JSON.stringify(data));
}