/**
	Copyright (c) 2008- Samuli Järvelä

	All rights reserved. This program and the accompanying materials
	are made available under the terms of the Eclipse Public License v1.0
	which accompanies this distribution, and is available at
	http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	this entire header must remain intact.
*/

var email = null;
var key = null;

function init(path, emailParam, keyParam) {
	servicePath = path;
	email = emailParam;
	key = keyParam;
	
	getSessionInfo(onSession, onError);
};

function onSession(session) {
	if (!session["authentication_required"]) {
		onError({error:"Configuration Error", details:"Current Mollify configuration does not require authentication, and registration is disabled"});
		return;
	}
	if (!session.features["registration"]) {
		onError({error:"Configuration Error", details:"Registration plugin not installed"});
		return;
	}
	
	if (!key) {
		$("#confirm-button").click(onDoConfirm);
		$("#confirmation-form").show();
	} else {
		confirm(email, key, onConfirmed, onError);
	}
}

function onDoConfirm() {
	$(".registration-field").removeClass("invalid");
	$(".registration-hint").html("");
	
	var keyValue = $("#key-field").val();
	if (!keyValue || keyValue.length == 0) {
		$("#key-field").addClass("invalid");
		$("#key-hint").html("Enter the confirmation key");
		return;
	}
	confirm(email, keyValue, onConfirmed, onError);
}

function onConfirmed(response) {
	if (response.error) {
		onError(response);
		return;
	}
	window.location = 'pages/registration_confirmed.html';
}

