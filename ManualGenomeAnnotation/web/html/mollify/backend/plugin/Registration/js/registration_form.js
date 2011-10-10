/**
	Copyright (c) 2008- Samuli Järvelä

	All rights reserved. This program and the accompanying materials
	are made available under the terms of the Eclipse Public License v1.0
	which accompanies this distribution, and is available at
	http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
	this entire header must remain intact.
*/

function init(path) {
	servicePath = path;
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
	$("#register-button").click(onRegister);
	$("#registration-form").show();
}

function onRegister() {
	$(".registration-field").removeClass("invalid");
	$(".registration-field-hint").html("");
	
	var name = $("#username-field").val();
	var pw = $("#password-field").val();
	var confirmPw = $("#confirm-password-field").val();
	var email = $("#email-field").val();
	
	if (name.length == 0) {
		$("#username-field").addClass("invalid");
		$("#username-hint").html("Enter the username");
	}
	if (pw.length == 0) {
		$("#password-field").addClass("invalid");
		$("#password-hint").html("Enter the password");
	}
	if (confirmPw.length == 0) {
		$("#confirm-password-field").addClass("invalid");
		$("#confirm-password-hint").html("Re-enter the password");
	}
	if (email.length == 0) {
		$("#email-field").addClass("invalid");
		$("#email-hint").html("Enter your email");
	}
	if (name.length == 0 || pw.length == 0 || confirmPw.length == 0 || email.length == 0) return;
	
	if (pw != confirmPw) {
		$("#password-field").addClass("invalid");
		$("#confirm-password-field").addClass("invalid");
		$("#password-hint").html("The passwords don't match");
		return;
	}
	
	register(name, pw, email, onRegistered, onError);
}

function onRegistered(response) {
	if (response.error) {
		onError(response);
		return;
	}
	window.location = 'pages/registration_success.html';
}
