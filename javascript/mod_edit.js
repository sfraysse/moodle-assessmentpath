
var statusDeleted = [];
var statusAdvanced = [];

// ---------- Events ---------- //

$(document).ready(function(){
	$("#tabs .tab").click(function() {
		setTab($(this).attr("id"), true);
	});
	$("#id_steps .commands .advancedstep").click(function() {
		setAdvanced($(this).attr("id"), true);
	});
	$("#id_steps .commands .deletestep").click(function() {
		setDeleted($(this).attr("id"), true);
	});
	$("#id_steps .commands .movedownstep").click(function() {
		moveDown($(this).attr("id"), true);
	});
	$("#id_steps .commands .moveupstep").click(function() {
		moveUp($(this).attr("id"), true);
	});
	$("input#id_submitbutton").click(function() {
		checkErrors();
	});
	$("input#id_submitbutton2").click(function() {
		checkErrors();
	});
	updateData(true);
	checkErrors();

});
	
// ---------- Updates ---------- //

function updateData(display) {
	$(".step").each(
		function(i) {
			var stepid = $(this).attr("id");
			var stepnum = stepid.substr(4, stepid.length);
			// Advanced
			statusAdvanced[stepid] = 0;
			// Remember deleted
			var deletedinput = "step_deleted["+stepnum+"]";
			statusDeleted[stepid] = $("input[name=\'"+deletedinput+"\']").val();
			// Assign ranks
			var rankinput = "step_rank["+stepnum+"]";
			$("input[name=\'"+rankinput+"\']").val(i);
		}
	);
	if (display) displayAll();
}

// ---------- Tabs ---------- //

function setTab(tab, display) {
	$("input[name=\'activetab\']").val(tab);
	if (display) displayTab();
}

function displayTab() {
	var tab = $("input[name=\'activetab\']").val();
	$("#tabs .tab").removeClass("active");
	if (tab == "maintab") {
		$("#tabs #maintab").addClass("active");
		$("#id_steps").hide();
		$("#id_general").show();
		$("#id_colorszone").show();
		$("#id_modstandardelshdr").show();
		$("#id_activitycompletionheader").show();
		$("#id_availabilityconditionsheader").show();
		$("#id_competenciessection").show();
		$("#id_tagshdr").show();
	} else {
		$("#tabs #stepstab").addClass("active");
		$("#id_steps").show();
		$("#id_general").hide();
		$("#id_colorszone").hide();
		$("#id_modstandardelshdr").hide();
		$("#id_activitycompletionheader").hide();
		$("#id_availabilityconditionsheader").hide();
		$("#id_competenciessection").hide();
		$("#id_tagshdr").hide();
	}
}
	
// ---------- Show / Hide advanced settings ---------- //
	
function setAdvanced(stepid, display) {
	statusAdvanced[stepid] = 1 - statusAdvanced[stepid];
	if (display) displayAdvanced();
}

function displayAdvanced() {
	$("#id_steps .step .advanced").hide();				
	$("#id_steps .step .advancedstep").removeClass("active");
	for (stepid in statusAdvanced) {
		if (statusAdvanced[stepid] == 1 && statusDeleted[stepid] != 1) {
			$("#id_steps .step#"+stepid+" .advanced").show();
			$("#id_steps .step#"+stepid+" .advancedstep").addClass("active");
		}
	}
}

// ---------- Delete ---------- //

function setDeleted(stepid, display) {
	statusDeleted[stepid] = 1 - statusDeleted[stepid];
	var stepnum = stepid.substr(4, stepid.length);
	var deletedinput = "step_deleted["+stepnum+"]";
	var codeinput = "step_code["+stepnum+"]";
	var titleinput = "step_title["+stepnum+"]";
	$("input[name=\'"+deletedinput+"\']").val(statusDeleted[stepid]);
	if (statusDeleted[stepid] == 1) {
		if ($("input[name=\'"+codeinput+"\']").val() == "") {
			$("input[name=\'"+codeinput+"\']").val("_");
		}
		if ($("input[name=\'"+titleinput+"\']").val() == "") {
			$("input[name=\'"+titleinput+"\']").val("_");
		}
	} else {
		if ($("input[name=\'"+codeinput+"\']").val() == "_") {
			$("input[name=\'"+codeinput+"\']").val("");
		}
		if ($("input[name=\'"+titleinput+"\']").val() == "_") {
			$("input[name=\'"+titleinput+"\']").val("");
		}
	}
	if (display) {
		displayDeleted();
		displayMoveUpDown();  // Impact on commands
	}
}

function displayDeleted() {
	var visiblenb = 0;
	var lastvisiblestepid = null;
	$("#id_steps .step .commands span").show();
	$("#id_steps .step .stepsettings").show();
	$("#id_steps .step .steptests").show();
	$("#id_steps .step .deletestep").removeClass("active");
	for (stepid in statusDeleted) {
		if (statusDeleted[stepid] == 1) {
			$("#id_steps .step#"+stepid+" .commands span").hide();
			$("#id_steps .step#"+stepid+" .stepsettings").hide();
			$("#id_steps .step#"+stepid+" .steptests").hide();
			$("#id_steps .step#"+stepid+" .deletestep").show();
			$("#id_steps .step#"+stepid+" .deletestep").addClass("active");
		} else {
			visiblenb++;
			lastvisiblestepid = stepid;
		}
	}
	if (visiblenb == 1) {
		$("#id_steps .step#"+lastvisiblestepid+" .deletestep").hide();
	}	
}
	
// ---------- Move Up & Down ---------- //

function moveUp(stepid, display) {
	var thisStep = $("#id_steps .step#"+stepid);
	var prevStep = thisStep.prev(".step");
	thisStep.fadeOut('slow', function() {
		prevStep.insertAfter(thisStep);
		thisStep.fadeIn('slow');
		if (display) displayMoveUpDown();
	});
}

function moveDown(stepid, display) {
	var thisStep = $("#id_steps .step#"+stepid);
	thisStep.fadeOut('slow', function() {
		thisStep.insertAfter(thisStep.next(".step"));
		thisStep.fadeIn('slow');
		if (display) displayMoveUpDown();
	});
}

function displayMoveUpDown() {
	var firststepid = null;
	var laststepid = null;
	$(".step").each(
		function(i) {
			var stepid = $(this).attr("id");
			var stepnum = stepid.substr(4, stepid.length);
			// Reassign range
			var rankinput = "step_rank["+stepnum+"]";
			$("input[name=\'"+rankinput+"\']").val(i);
			// Remember first and last IDs
			if (i == 0) firststepid = stepid;
			laststepid = stepid;
			// Display move commands if not deleted
			if (statusDeleted[stepid] != 1) $("#id_steps .step#"+stepid+" .moveupstep").show();
			if (statusDeleted[stepid] != 1) $("#id_steps .step#"+stepid+" .movedownstep").show();
		}
	);
	$("#id_steps .step#"+firststepid+" .moveupstep").hide();
	$("#id_steps .step#"+laststepid+" .movedownstep").hide();
}
	
// ---------- Display all ---------- //

function displayAll() {
	displayTab();
	displayAdvanced();
	displayDeleted();
	displayMoveUpDown();
}

// ---------- Check errors ---------- //

function checkErrors() {
	var found = false;
	$("#id_general span.error").each(
		function(i) {
			if (!found) {
				found = true;
				setTab("maintab", true);
			}
		}
	);
	if (!found) {
		$("#id_steps .advanced span.error").each(
			function(i) {
				if (!found) {
					found = true;
					var stepid = $(this).closest(".step").attr("id");
					statusAdvanced[stepid] = 1;
					displayAdvanced();
					setTab("stepstab", true);
				}
			}
		);
	}
	if (!found) {
		$("#id_steps span.error").each(
			function(i) {
				if (!found) {
					found = true;
					setTab("stepstab", true);
				}
			}
		);
	}
}


