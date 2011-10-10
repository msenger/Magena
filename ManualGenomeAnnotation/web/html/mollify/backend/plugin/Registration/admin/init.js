function initRegistration() {
	return {
		views: [
			{header:"Registrations", id:'menu-header-registrations', views: [
				{title:"Pending", id:'menu-registrations-pending', "class": "PendingRegistrationsView", "script": "registrations.js"}
			]}
		]
	};
}