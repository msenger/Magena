function initEventLogging() {
	return {
		views: [
			{header:"Events", id:'menu-header-events', views: [
				{title:"All Events", id:'menu-events-all', "class": "MollifyEventsView", "script": "events.js"},
				{title:"Downloads", id:'menu-events-downloads', "class": "MollifyDownloadsView", "script": "downloads.js"}
			]}
		]
	};
}