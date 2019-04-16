<?php

/* * *************************************************************
 *  This script has been developed for Moodle - http://moodle.org/
 *
 *  You can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
  *
 * ************************************************************* */

define('COMMENT_CONTEXT_GROUP_PATH', 1);	// Report P3			
define('COMMENT_CONTEXT_GROUP_STEP', 2);	// Report P4
define('COMMENT_CONTEXT_USER_PATH', 3);		// Report P1
define('COMMENT_CONTEXT_USER_COURSE', 4);	// Report P1
define('COMMENT_CONTEXT_GROUP_COURSE', 5);	// Report P2


// 
// Modify results
//

// Modify scores

function assessmentpath_set_step_users_scores($usersscores, $stepid, $remediation = 0) {
	// Get scoes
	global $DB;
	$sql = "
		SELECT T.sco AS scoid, SS.passingscore
		FROM {assessmentpath_steps} S
		INNER JOIN {assessmentpath_tests} T ON T.step=S.id AND T.remediation=$remediation
		INNER JOIN {scormlite_scoes} SS ON SS.id=T.sco
		WHERE S.id=$stepid";
	$records = $DB->get_recordset_sql($sql);
	$scoid = 0;
	$passingscore = 0;
	foreach ($records as $record) {
		$scoid = $record->scoid;
		$passingscore = $record->passingscore;
		break;
	}
	if (empty($scoid) || empty($passingscore)) return;
	// Change score
	foreach ($usersscores as $userid => $newscore) {
		$scaled = $newscore/100;
		$DB->set_field('scormlite_scoes_track', 'value', $scaled, array('element'=>'cmi.score.scaled', 'scoid'=>$scoid, 'userid'=>$userid));
		$DB->set_field('scormlite_scoes_track', 'value', $newscore, array('element'=>'cmi.score.raw', 'scoid'=>$scoid, 'userid'=>$userid));
		if ($newscore >= $passingscore) {
			$DB->set_field('scormlite_scoes_track', 'value', 'passed', array('element'=>'cmi.success_status', 'scoid'=>$scoid, 'userid'=>$userid));			
		} else {
			$DB->set_field('scormlite_scoes_track', 'value', 'failed', array('element'=>'cmi.success_status', 'scoid'=>$scoid, 'userid'=>$userid));			
		}
	}	
}

// 
// Get data structures
//

// Populate activities

function assessmentpath_report_populate_activities(&$activities, $courseid, $groupingid) {
    
	global $CFG;
	require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
	$scoids = array();
    
	scormlite_report_populate_activities($activities, $courseid, $groupingid, "assessmentpath");
	
    foreach ($activities as $activityid => $activity) {
		$activity->initial_tests = array();
		$activity->remediation_tests = array();
		$scoids1 = assessmentpath_report_populate_steps($activity->initial_tests, $activityid, 0);
		$scoids2 = assessmentpath_report_populate_steps($activity->remediation_tests, $activityid, 1);
		$scoids = array_merge($scoids, $scoids1, $scoids2);
		$activities[$activityid] = $activity;
	}
	return $scoids;
}

// Populate steps

function assessmentpath_report_populate_steps(&$steps, $activityid, $remediation = 0) {
	global $DB;
	$sql = "
		SELECT S.id, S.code, S.title, S.rank, T.sco AS scoid
		FROM {assessmentpath} A
		INNER JOIN {assessmentpath_steps} S ON S.activity=A.id
		INNER JOIN {assessmentpath_tests} T ON T.step=S.id AND T.remediation=$remediation
		INNER JOIN {scormlite_scoes} SS ON SS.id=T.sco
		WHERE A.id=$activityid";
	$scoids = array();
	$records = $DB->get_recordset_sql($sql);
	foreach ($records as $record) {
		if (!array_key_exists($record->id, $steps)) {
			$step = new stdClass();
			$step->id = $record->id;
			$step->code = $record->code;
			$step->title = $record->title;
			$step->rank = $record->rank;
			$step->scoid = $record->scoid;
			$steps[$step->id] = $step;
			$scoids[] = $step->scoid;
		}
	}
	uasort($steps, 'assessmentpath_report_compare_steps_by_rank');
	return $scoids;
}

function assessmentpath_report_compare_steps_by_rank($step_record1, $step_record2) {
	if ($step_record1->rank == $step_record2->rank) return 0;
	return $step_record1->rank < $step_record2->rank ? -1 : 1;
}

function assessmentpath_report_populate_step(&$step) {
	global $DB;
	$stepid = $step->id;
	$sql = "
		SELECT S.id, S.code, S.title, S.rank, T.sco AS scoid, T.remediation
		FROM {assessmentpath_steps} S
		INNER JOIN {assessmentpath_tests} T ON T.step=S.id
		INNER JOIN {scormlite_scoes} SS ON SS.id=T.sco
		WHERE S.id=$stepid
        ORDER BY T.id DESC";
	$scoids = array();
	$records = $DB->get_recordset_sql($sql);
	foreach ($records as $record) {
		if ($record->remediation == 1) $step->remediation_scoid = $record->scoid;
		else $step->initial_scoid = $record->scoid;
		$scoids[] = $record->scoid;
	}
	return $scoids;
}

// Sorting functions

function assessmentpath_report_sort_course_activities($activities, $courseid) {
    global $DB;
    $sql = "
            SELECT S.id, S.sequence AS seq
            FROM {course_sections} S
            WHERE S.course=".$courseid."
    ";
    $records = $DB->get_records_sql($sql);
    $seq = "";
    foreach ($records as $record) {
        if (empty($seq)) $seq = $record->seq;
        else $seq .= ','.$record->seq;
    }
    $res = array();
    $seq = explode(',', $seq);
    foreach ($seq as $cmid) {
        foreach ($activities as $key=>$activity) {
            if ($activity->cmid == $cmid) {
                $res[$key] = $activity;
                break;
            }
        }
    }
    return $res;
}


//
// Get results
//

function assessmentpath_report_populate_user_results(&$activities, &$user, $scoids, $closedonly = true) {
	$userids = array($user->id);
	$attempts = assessmentpath_report_get_attempts($scoids, $userids, $closedonly);
	if (array_key_exists($user->id, $attempts)) $attempts = $attempts[$user->id];
	else $attempts = array();
	// Populate data
	foreach ($activities as $activityid => $activity) {
		$activity->initial_scores = array(); 
		$activity->initial_avg = null;
		$activity->remediation_scores = array(); 
		$activity->remediation_avg = null;
		// Initial steps
		$steps = $activity->initial_tests;			
		foreach ($steps as $stepid => $step) {
			if (array_key_exists($step->scoid, $attempts)) {
				if (isset($attempts[$step->scoid]->score_display)) {
					$activity->initial_scores[$stepid] = $attempts[$step->scoid]->score_display;
				}
			}
		}
		// Remediation steps
		$steps = $activity->remediation_tests;			
		foreach ($steps as $stepid => $step) {
			if (array_key_exists($step->scoid, $attempts)) {
				if (isset($attempts[$step->scoid]->score_display)) {
					$activity->remediation_scores[$stepid] = $attempts[$step->scoid]->score_display;
				}
			}
		}
		// Averages
		if (!empty($activity->initial_scores)) $activity->initial_avg = array_sum($activity->initial_scores) / count($activity->initial_scores);
		if (!empty($activity->remediation_scores)) $activity->remediation_avg = array_sum($activity->remediation_scores) / count($activity->remediation_scores);
		// The end
		$activities[$activityid] = $activity;
	}
}

function assessmentpath_report_populate_course_results(&$activities, &$users, $scoids, $userids, $closedonly = true) {
	return assessmentpath_report_populate_users_results($activities, $users, $scoids, $userids, $closedonly, 'initial_tests');
}

function assessmentpath_report_populate_activity_results(&$steps, &$users, $scoids, $userids, $closedonly = true) {
	return assessmentpath_report_populate_users_results($steps, $users, $scoids, $userids, $closedonly);
}

function assessmentpath_report_populate_users_results(&$contents, &$users, $scoids, $userids, $closedonly = true, $contentchildren = '') {
	$attempts = assessmentpath_report_get_attempts($scoids, $userids, $closedonly);
	// Populate data
	$global_avg = null;
	$global_scores = array();
	foreach ($users as $userid => $user) {
		// User data
		$user->scores = array();
		$user->avg = null;
		$user->scorerank = null;
		foreach ($contents as $contentid => $content) {
			// Content data
			if (!isset($content->scores)) {
				$content->scores = array(); 
				$content->avg = null;
			}
			// Get score
			if (empty($contentchildren)) {
				$score = null;
				if (array_key_exists($userid, $attempts) && array_key_exists($content->scoid, $attempts[$userid])) {
					if (isset($attempts[$userid][$content->scoid]->score_display)) {
						$score = $attempts[$userid][$content->scoid]->score_display;
					}
				}
			} else {
				$score = assessmentpath_report_get_average_score($content->{$contentchildren}, $userid, $attempts);
			}
			// Average per user+content
			if (isset($score)) {
				$user->scores[$contentid] = $score;
				if (!$user->trainer) {  // Do not include trainers
					$content->scores[$userid] = $score;  
				}
			}
			$contents[$contentid] = $content;
		}
		// Average per user
		if (!empty($user->scores)) {
			$avg = array_sum($user->scores) / count($user->scores);
			$user->avg = $avg;
			if (!$user->trainer) { // Do not include trainers
				$user->scorerank = $avg;
				$global_scores[] = $avg;  
			}
		}
		$users[$userid] = $user;
	}
	scormlite_report_set_users_ranks($users);
	uasort($users, 'scormlite_report_compare_users_by_name');
	// Average per content
	foreach ($contents as $contentid => $content) {
		if (!empty($content->scores)) {
			$content->avg = array_sum($content->scores) / count($content->scores);
		}
	}
	// Statistics
	if (!empty($global_scores)) {
		$global_avg = array_sum($global_scores) / count($global_scores);
	}
	return $global_avg;
}

function assessmentpath_report_populate_course_progress(&$activities, &$users, $scoids, $userids) {
	global $DB;
	$attempts = assessmentpath_report_get_attempts($scoids, $userids);
	// Statistics
	$statistics = new stdClass();
	$statistics->number = 0;
	$statistics->completed = 0;
	$statistics->progress = 0;
	// Populate data
	foreach ($activities as $activityid => $activity) {
		$steps = $activity->initial_tests;	// Search on initial tests only		
		$number = count($steps);
		$completed = 0;
		foreach ($steps as $stepid => $step) {
			// Search for the first user result (if one, it means that the test is closed)
			$found = false;
			$scoid = $step->scoid;
			$sco = $DB->get_record("scormlite_scoes", array("id" => $scoid), '*', MUST_EXIST);
			if ($sco->manualopen == 2 || ($sco->manualopen == 0 && time() > $sco->timeclose)) {
				foreach ($attempts as $userid => $scos) {
					if (array_key_exists($scoid, $scos)) {
						if (isset($scos[$scoid]->score_display)) {
							$found = true;
							$statistics->completed += 1;
							$completed += 1;
							break;
						}
					}
				}
			}
			$statistics->number += 1;
			$step->completed = $found;
			$steps[$stepid] = $step;
		}
		$activity->progress = 0;
		if ($number > 0) $activity->progress = 100 * $completed / $number;
		$activities[$activityid] = $activity;
	}			
	// Return 
	if ($statistics->number > 0) {
		$statistics->progress = 100 * $statistics->completed / $statistics->number;
		return $statistics;
	} else {
		return false;
	}
}

function assessmentpath_report_populate_step_results($step, $users, $scoids, $userids, $closedonly = true) {
	$attempts = assessmentpath_report_get_attempts($scoids, $userids, $closedonly);
	// Statistics
	$scores_remediation_beforeremediation = array();
	$scores_remediation_afterremediation = array();
	$scores_group_beforeremediation = array();
	$scores_group_afterremediation = array();
	// Populate data
	foreach ($users as $userid => $user) {
		// Initial score
		$user->initial_score = null;
		if (array_key_exists($userid, $attempts) && array_key_exists($step->initial_scoid, $attempts[$userid])) {
			$attempt = $attempts[$userid][$step->initial_scoid];
			if (isset($attempt->attempt)) {
				$user->initial_attempt = $attempt->attempt . '/' . $attempt->attemptnb;
			}
			if (isset($attempt->score_display)) {
				$user->initial_score = $attempt->score_display;
			}
		}
		if (!$user->trainer) { // Do not include trainers
			$user->scorerank = $user->initial_score;
		}
		// Remediation score
		$user->remediation_score = null;
		if (isset($step->remediation_scoid) && array_key_exists($userid, $attempts) && array_key_exists($step->remediation_scoid, $attempts[$userid])) {
			$attempt = $attempts[$userid][$step->remediation_scoid];
			if (isset($attempt->attempt)) {
				$user->remediation_attempt = $attempt->attempt . '/' . $attempt->attemptnb;
			}
			if (isset($attempt->score_display)) {
				$user->remediation_score = $attempt->score_display;
			}
		}
		// Statistics
		if (!$user->trainer) {
			if (isset($user->remediation_score)) {
				// For those who are in remediation
				$scores_remediation_beforeremediation[] = $user->initial_score;
				$scores_remediation_afterremediation[] = $user->remediation_score;
				$scores_group_afterremediation[] = $user->remediation_score;
			} else if (isset($user->initial_score)) {
				// For those who are NOT in remediation
				$scores_group_afterremediation[] = $user->initial_score;
			}
			// For all
			if (isset($user->initial_score)) $scores_group_beforeremediation[] = $user->initial_score;
		}
		// The end
		$users[$userid] = $user;
	}
	scormlite_report_set_users_ranks($users);
	uasort($users, 'scormlite_report_compare_users_by_name');
	// Statistics
	$statistics = new stdClass();
	$statistics->avg_remediation_beforeremediation = empty($scores_remediation_beforeremediation) ? null : array_sum($scores_remediation_beforeremediation) / count($scores_remediation_beforeremediation);
	$statistics->avg_remediation_afterremediation = empty($scores_remediation_afterremediation) ? null : array_sum($scores_remediation_afterremediation) / count($scores_remediation_afterremediation);
	$statistics->avg_group_beforeremediation = empty($scores_group_beforeremediation) ? null : array_sum($scores_group_beforeremediation) / count($scores_group_beforeremediation);
	$statistics->avg_group_afterremediation = empty($scores_group_afterremediation) ? null : array_sum($scores_group_afterremediation) / count($scores_group_afterremediation);
	return $statistics;
}

function assessmentpath_report_get_attempts($scoids, $userids, $closedonly = true)
{
	if (empty($userids) || empty($scoids)) return array();
	$res = [];
	foreach($scoids as $scoid) {
		$attempts = scormlite_get_tracks($scoid);
		if (!$attempts) continue;
		foreach ($attempts as $userid => $attempt) {
			if (isset($attempt->score_scaled)) {
				$attempt->score_display = floatval($attempt->score_scaled) * 100;
			}
			if (!isset($res[$userid])) $res[$userid] = [];
			$res[$userid][$scoid] = $attempt;
		}
	}
	return $res;
}

function assessmentpath_report_get_average_score($contents, $userid, $attempts) {
	// Steps
	$myscores = array();
	$avg = null;
	foreach ($contents as $contentid => $content) {
		if (array_key_exists($userid, $attempts) && array_key_exists($content->scoid, $attempts[$userid])) {
			$score = $attempts[$userid][$content->scoid]->score_display;
			if (isset($score)) $myscores[$contentid] = $score;
		}
	}
	// Average per user+content
	if (!empty($myscores)) {
		$avg = array_sum($myscores) / count($myscores);
	}
	return $avg;
}

//
// Print result tables
//

require_once($CFG->dirroot.'/lib/tablelib.php');

class assessmentpath_workbook {

	private $format;
	private $type;
	
	private $exporter;
	
	private $codes = array();  // To prevent identical codes in the same workbook

	function __construct($format, $type) {
		$this->format = $format;
		$this->type = $type;
		// Exporter
		if ($format == 'csv') {
			$this->exporter = new scormlite_table_csv_export_format("assessmentpath");
		} else if ($format == 'xls') {
			$this->exporter = new scormlite_table_xls_export_format("assessmentpath");
		} else if ($format == 'html') {
			$this->exporter = new scormlite_table_html_export_format("assessmentpath");
		} else {
			$this->exporter = new scormlite_table_lms_export_format("assessmentpath");
		}
	}
	
	function add_worksheet($code = '', $titles = array(), $colwidth = array(), $colnumber = null) {
		while(in_array($code, $this->codes)) {
			$code = $code.'.';
		}
		$this->codes[] = $code;
		return new assessmentpath_worksheet($this->format, $this->type, $this->exporter, $code, $titles, $colwidth, $colnumber);
	}

	function close() {	
		$this->exporter->finish_document();
	}
	
}

class assessmentpath_worksheet {
	
	private $format;
	private $type;
	
	private $exporter;
	
	private $preworksheet = '';
	private $postworksheet = '';

	private $sectiontitles = array();
	private $tables = array();
	private $comments = array();
	
	function __construct($format, $type, &$exporter, $code = '', $titles = array(), $colwidth = array(), $colnumber = null) {
		$this->format = $format;
		$this->type = $type;
		$this->exporter = $exporter;
		// Exporter
		if ($format == 'xls') {
			$this->exporter->start_worksheet($code, $titles, $colwidth, $colnumber);
		}
	}
	
	function add_pre_worksheet($content) {
		$this->preworksheet .= $content;
	}
	
	function add_post_worksheet($content) {
		$this->postworksheet .= $content;
	}
	
	function start_section($title, $index = null) {
		if (!isset($index)) $this->sectiontitles[] = $title;
		else $this->sectiontitles[$index] = $title;
	}
	
	function add_table($table, $index = null) {
		if (!isset($index)) $this->tables[] = $table;
		else $this->tables[$index] = $table;
	}
	
	function add_comment($content, $index = null) {
		if (!isset($index)) $this->comments[] = $content;
		else $this->comments[$index] = $content;
	}

	function display() {
		// Pre-Worksheet
		if ($this->format == 'html' || $this->format == 'lms') {
			echo $this->preworksheet;
		}		
		// Sections
		foreach ($this->tables as $index=>$table) {
            
			// Section title
			if (isset($this->sectiontitles[$index])) {
				if ($this->format == 'html' || $this->format == 'lms') {
					echo '<div class="activity">';
					echo '<h4>'.$this->sectiontitles[$index].'</h4>';
				} else if ($this->format == 'xls') {
					$this->exporter->add_section_title($this->sectiontitles[$index]);			
				}
			}
			// Table
			$table->display($this->format, $this->type, $this->exporter);
			// Comment
			if (isset($this->comments[$index])) {				
				if ($this->format == 'html' || $this->format == 'lms') {
					echo $this->comments[$index];
				} else if ($this->format == 'xls' && !empty($this->comments[$index])) {
					$this->exporter->add_comment($this->comments[$index]);
				}
			}					
			// End of section
			if (isset($this->sectiontitles[$index])) {
				if ($this->format == 'html' || $this->format == 'lms') {
					echo '</div>';
				} else if ($this->format == 'xls') {
					$this->exporter->add_break();
				}
			}
		}
		// Post-Worksheet
		if ($this->format == 'html' || $this->format == 'lms') {
			echo $this->postworksheet;
		} else if ($this->format == 'xls') {		
			$this->exporter->add_legend();
			if (!empty($this->postworksheet)) {
				$this->exporter->add_comment($this->postworksheet);
			}
		}
	}
}

class assessmentpath_report_table {
	// Definition
	protected $courseid = null;
	protected $elements = null;
	protected $url = null;
	protected $content = null;
	protected $context = null;
	// Data
	protected $scores = null;
	protected $rows = array();
	// Presentation
	protected $colors = null;
	protected $fullmode = null;
	
	function __construct($courseid, $elements, $url, $content = null) {
		$this->courseid = $courseid;
		$this->elements = $elements;
		$this->url = $url;
		$this->content = $content;
		$this->context = context_course::instance($this->courseid, MUST_EXIST);
	}

	public function define_presentation($colors = null, $fullmode = false) {
		$this->colors = $colors;
		$this->fullmode = $fullmode;
	}
	
	public function add_scores($title, $scores) {
		$row = array($title);
		foreach ($scores as $score) {
			if (isset($score)) {
				$row[] = array('score' => sprintf("%01.1f", $score), 'colors' => $this->colors);
			} else {
				$row[] = '';
			}
		}
		$this->rows[] = $row;
	}
	
	public function add_tests($title, $tests, $avg, $userid, $remediation = 0, $links = true) {
		$row = array();
		foreach ($this->elements as $element) {
			if (is_array($element)) {
				// Scores
				foreach ($element as $eltid => $elt) {
					if (array_key_exists($eltid, $tests) && isset($tests[$eltid])) {
						$review_link = null;
						if ($remediation == 0) $scoid = $elt->scoid;
						else $scoid = $this->content[$eltid]->scoid;
						$reviewallowed = has_capability('mod/scormlite:reviewmycontent', $this->context);
						if ($reviewallowed) $review_link = scormlite_report_get_link_review($scoid, $userid, $this->url);
						if ($links == true) $row[] = array('score' => sprintf("%01.1f", $tests[$eltid]), 'colors' => $this->colors, 'link' => $review_link);
						else $row[] = array('score' => sprintf("%01.1f", $tests[$eltid]), 'colors' => $this->colors);
					} else {
						$row[] = "";
					}
				}
			} else {
				// Simple data
				if ($element == 'testcaption') {
					$row[] = $title;
				} else if ($element == 'avg') {
					if (isset($avg)) $row[] = array('score' => sprintf("%01.1f", $avg), 'colors' => $this->colors);
					else $row[] = '';
				}
			}
		}
		$this->rows[] = $row;
	}
	
	public function add_users($users, $links = true) {
		global $OUTPUT;
		foreach ($users as $userid => $user) {
			$row = array();
			foreach ($this->elements as $element) {
				if (is_array($element)) {
					// Scores
					foreach ($element as $eltid => $elt) {
						if (array_key_exists($eltid, $user->scores) && isset($user->scores[$eltid])) {
							if (isset($elt->colors)) $color = $elt->colors;
							else $color = $this->colors;
							$row[] = array('score' => sprintf("%01.1f", $user->scores[$eltid]), 'colors' => $color);
						} else {
							$row[] = "";
						}
					}
				} else {
					// Simple data
					if ($element == 'picture') {
						$row[] = $OUTPUT->user_picture($user);	
					} else if ($element == 'fullname') {
						if ($links == true)	$row[] = assessmentpath_report_get_link_P1($this->courseid, $user->id).' '.$user->lastname." ".$user->firstname;
						else $row[] = $user->lastname." ".$user->firstname; 
					} else if ($element == 'avg') {
						if (isset($user->avg)) $row[] = array('score' => sprintf("%01.1f", $user->avg), 'colors' => $this->colors);
						else $row[] = '';
					} else if ($element == 'rank') {
						$row[] = $user->rank;
					} else if ($element == 'initialscore') {
						if (isset($user->initial_score)) {
							$review_link = null;
							$reviewallowed = has_capability('mod/scormlite:reviewothercontent', $this->context);	
							if ($reviewallowed) $review_link = scormlite_report_get_link_review($this->content->initial_scoid, $user->id, $this->url);
							if ($links == true) {
								$cell = array('score' => sprintf("%01.1f", $user->initial_score), 'colors' => $this->colors, 'link' => $review_link);
							} else {
								$cell = array('score' => sprintf("%01.1f", $user->initial_score), 'colors' => $this->colors);
							}
							if (isset($user->initial_attempt)) {
								$cell['attempt'] = $user->initial_attempt;
							}
							$row[] = $cell;
						} else {
							$row[] = '';
						}
					} else if ($element == 'remediationscore') {
						if (isset($user->remediation_score)) {
							$review_link = null;
							$reviewallowed = has_capability('mod/scormlite:reviewothercontent', $this->context);	
							if ($reviewallowed) $review_link = scormlite_report_get_link_review($this->content->remediation_scoid, $user->id, $this->url);
							if ($links == true) {
								$cell = array('score' => sprintf("%01.1f", $user->remediation_score), 'colors' => $this->colors, 'link' => $review_link);
							} else { 
								$cell = array('score' => sprintf("%01.1f", $user->remediation_score), 'colors' => $this->colors);
							}
							if (isset($user->remediation_attempt)) {
								$cell['attempt'] = $user->remediation_attempt;
							}
							$row[] = $cell;
						} else {
							$row[] = '';
						}
					} else {
						$last = $row[count($row)-1];
						$exp = explode('_', $element);
						if ($exp[0] == 'scorefield') {
							if (empty($last)) $row[] = '';
							else $row[] = '<input type="text" class="scorefield" name="'.$element.'_'.$userid.'" id="'.$element.'_'.$userid.'" maxlength="4" size="4"/>';
						}
					}
				}
			}
			// Row class
			if ($user->trainer) $row['class'] = "trainer";
			// The end
			$this->rows[] = $row;
		}
	}
	
	public function add_average($scored_elements, $avg = null) {
		$row = array();
		foreach ($this->elements as $element) {
			if (is_array($element)) {
				// Scores
				foreach ($element as $eltid => $elt) {
					if (array_key_exists($eltid, $scored_elements) && isset($scored_elements[$eltid]) && isset($scored_elements[$eltid]->avg)) {
						$score = sprintf("%01.1f", $scored_elements[$eltid]->avg);
						if (isset($scored_elements[$eltid]->colors)) $color = $scored_elements[$eltid]->colors;
						else $color = $this->colors;
						$row[] = array('score' => $score, 'colors' => $color);
					} else {
						$row[] = "";
					}
				}
			} else {
				// Simple data
				if ($element == 'picture') {
					$row[] = '';
				} else if ($element == 'fullname') {
					$row[] = get_string('averagescore', 'scormlite');
				} else if ($element == 'avg') {
					if (isset($avg)) $row[] = array('score' => sprintf("%01.1f", $avg), 'colors' => $this->colors);
					else $row[] = '';
				} else if ($element == 'rank') {
					$row[] = null;
				}
			}
		}
		$row['class'] = "average";
		$this->rows[] = $row;		
	}

	public function display($format, $type, $exporter) {
		$table = $this->define_table($format, $type, $exporter);
		$table->start_output();
		foreach($this->rows as $row) {
			$table->add_data($row);
		}
		$table->finish_output(false);		
	}
	
	// --------------- Private ------------------
	
	private function define_table($format, $type, $exporter) {
		$export = $format.'_'.$type;
		$table = new flexible_table('mod-assessmentpath-report');
		$columns = array();
		$headers = array();
		foreach($this->elements as $element) {
			if (is_array($element)) {
				// Scores
				$this->scores = $element;
				foreach ($element as $columnid => $column) {
					$columns[] = $columnid;
					if ($export == "lms_P1") {
						if ($this->fullmode) $headers[] = $column->code.'<br/>'.assessmentpath_report_get_link_P4($column->id);
						else $headers[] = $column->code;
					} else if ($export == "lms_P2") {
						$headers[] = $column->code.'<br/>'.assessmentpath_report_get_link_P3($column->cmid);
					} else if ($export == "lms_P3") {
						$headers[] = $column->code.'<br/>'.assessmentpath_report_get_link_P4($column->id);
					} else {
						$headers[] = $column->code;
					}
				}
			} else {
				// Simple data
				$columns[] = $element;
				if ($format == 'csv') $headers[] = $element;
				else if ($element == 'picture') $headers[] = '';
				else if ($element == 'fullname') $headers[] = get_string('learner', 'scormlite');
				else if ($element == 'avg') $headers[] = get_string('averagescore_short', 'scormlite');
				else if ($element == 'rank') $headers[] = get_string('rank', 'scormlite');
				else if ($element == 'testcaption') $headers[] = get_string('test', 'assessmentpath');
				else if ($element == 'initialscore') {
					if ($format == 'lms') $headers[] = $this->content->code.'<br/>'.assessmentpath_report_get_link_statistics($this->content->initial_scoid, $this->content->id);
					else $headers[] = $this->content->code;
				} else if ($element == 'remediationscore') {

					// SF2018 - Check remledial test existence
					if (isset($this->content->remediation_scoid)) {
						if ($format == 'lms') $headers[] = $this->content->code.'_R'.'<br/>'.assessmentpath_report_get_link_statistics($this->content->remediation_scoid, $this->content->id);
						else $headers[] = $this->content->code.'_R';
					} else {
						$headers[] = '';
					}
					
				}
				else if ($element == 'title') $headers[] = '';
				else if (!empty($element)) $headers[] = get_string($element, 'assessmentpath');
				else $headers[] = '';
			}
		}
		// Defs
		$table->define_columns($columns);
		$table->define_headers($headers);
		$table->define_baseurl($this->url);
		// Styles
		if (in_array("picture", $this->elements)) $table->column_class('picture', 'picture');
		if (in_array("fullname", $this->elements)) $table->column_class('fullname', 'fullname');
		if (in_array("avg", $this->elements)) $table->column_class('avg', 'avg');
		if (in_array("rank", $this->elements)) $table->column_class('rank', 'rank');
		if (in_array("testcaption", $this->elements)) $table->column_class('testcaption', 'caption');
		if (in_array("initialscore", $this->elements)) $table->column_class('initialscore', 'initial');
		if (in_array("remediationscore", $this->elements)) $table->column_class('remediationscore', 'remediation');
		if (in_array("beforeremediation", $this->elements)) $table->column_class('beforeremediation', 'beforeremediation');
		if (in_array("afterremediation", $this->elements)) $table->column_class('afterremediation', 'afterremediation');
		if (in_array("title", $this->elements)) $table->column_class('title', 'title');
		if (in_array("scorefield", $this->elements)) $table->column_class('scorefield', 'scorefield');
		if (in_array("scorefield_R", $this->elements)) $table->column_class('scorefield_R', 'scorefield');
		if (isset($this->scores)) {
			foreach ($this->scores as $columnid => $column) {
				$table->column_class($columnid, 'score');
			}
		}
		// Export
		//$table->sheettitle = '';
		$table->export_class_instance($exporter);
		// Final setup
		$table->setup();
		return $table;
	}
}

class assessmentpath_progress_table {
	// Definition
	protected $url = '';
	// Data
	protected $rows = array();
	protected $size = 0;
	
	public function add_activities($activities) {
		foreach ($activities as $activity) {
			$row = array($activity->code);
			$row[] = $activity->progress;
			foreach($activity->initial_tests as $stepid=>$step) {
				$row[] = $step;  // Includes Code and Completed props. Will be threated by the display layout.
			}
			$this->size = max(array($this->size, count($activity->initial_tests))); 
			$this->rows[] = $row;
		}
	}
	
	public function display($export) {
		$table = $this->define_table($export);
		$table->start_output();
		foreach($this->rows as $row) {
			$table->add_data($row);
		}
		$table->finish_output();		
	}
	
	// --------------- Private ------------------
	
	private function define_table($export) {
		$table = new flexible_table('mod-assessmentpath-report');
		$columns = array('title', 'progress');
		$headers = array(get_string('path', 'assessmentpath'), get_string('progress', 'scormlite'));
		for ($i=0; $i<$this->size; $i++) {
			$columns[] = 'step'.$i;
			$headers[] = '';
		}
		// Defs
		$table->define_columns($columns);
		$table->define_headers($headers);
		$table->define_baseurl($this->url);
		// Styles
		$table->column_class('title', 'title');
		$table->column_class('progress', 'progress');
		// Export
		//$table->sheettitle = '';
		// if $export contains "lms"
		$row_exporter = new assessmentpath_table_progress_export_format($table);
		$table->export_class_instance($row_exporter);
		// Final setup
		$table->setup();
		return $table;
	}
}

require_once($CFG->dirroot.'/lib/tablelib.php');

class assessmentpath_table_progress_export_format extends table_default_export_format_parent {

	// Write the table row
	
	public function add_data($row) {
		global $OUTPUT;
		// Content row
		echo html_writer::start_tag('tr', array());
		$colbyindex = array_flip($this->table->columns);
		foreach ($row as $index => $data) {
			$column = $colbyindex[$index];
			$style = '';
			$cellclass = '';
			if ($index == 0) {
				// Title
				$cell = $data;
			} else if ($index == 1) {
				// Progress
				$cell = sprintf("%01.1f", $data).'%';
			} else {
				// Step object, including Code and Completed props
				$cell = $data->code;
				if ($data->completed == true) $cellclass = ' completed';
				else $cellclass = ' incomplete';				
			}
			// Cell
			echo html_writer::tag('td', $cell, array(
				'class' => 'cell c' . $index . $this->table->column_class[$column].$cellclass,
				'style' => $this->table->make_styles_string($this->table->column_style[$column]).$style));
		}
		// Space row
		echo html_writer::start_tag('tr', array('class'=>'vspace'));
		foreach ($row as $index => $data) {
			echo html_writer::tag('td', '');
		}
		echo html_writer::end_tag('tr');
		echo html_writer::end_tag('tr');
	}
	
	// Other herited functions
	
	public function start_table($title) {
		$this->table->start_html();
	}
	public function output_headers($headers) {
		$this->table->print_headers();
	}
	public function finish_table() {
		$this->table->finish_html();
	}
	public function finish_document() {
	}
}


// 
// Print functions
//

function assessmentpath_report_print_activity_header_html($cm, $activity, $course, $titlelink = '', $subtitle = '', $bodyid = null, $bodyclass = null) {
	$pagetitle = scormlite_print_header_html($activity, $bodyid, $bodyclass);
	echo '<div class="generalbox mdl-align">';
	if (empty($cm->groupingid)) {
		echo '<p>'.get_string('noactivitygrouping', 'scormlite').'</p>';
		echo '</div>';
		scormlite_print_footer_html();
		exit;
	} else {
		scormlite_print_activity_grouping($cm, $activity);
        scormlite_print_title($cm, $activity, $titlelink);
        if (!empty($subtitle)) echo '<h3 class="mdl-align step">'.$subtitle.'</h3>';
        echo '</div>';
        return $pagetitle;
	}
}

function assessmentpath_report_print_activity_header($cm, $activity, $course, $titlelink = '', $subtitle = '') {
	global $OUTPUT, $CFG;
	// Start
	$pagetitle = scormlite_print_header($cm, $activity, $course);
	// Tabs
	$playurl = "$CFG->wwwroot/mod/assessmentpath/view.php?id=$cm->id";
	$reporturl = "$CFG->wwwroot/mod/assessmentpath/report/P3.php?id=$cm->id";
	scormlite_print_tabs($cm, $activity, $playurl, $reporturl, 'report');
	// Title box
	echo $OUTPUT->box_start('generalbox mdl-align');
	if (empty($cm->groupingid)) {
		echo '<p>'.get_string('noactivitygrouping', 'scormlite').'</p>';
		echo $OUTPUT->box_end();
		echo $OUTPUT->footer();
		exit;
	} else {
		$groupinglink = assessmentpath_report_get_link_P2($course->id, $cm->groupingid);
		scormlite_print_activity_grouping($cm, $activity, $groupinglink);
		scormlite_print_title($cm, $activity, $titlelink);
		if (!empty($subtitle)) echo '<h3 class="mdl-align step">'.$subtitle.'</h3>';
		echo $OUTPUT->box_end();
		return $pagetitle;
	}
}

function assessmentpath_report_get_url_P1($courseid, $userid = null, $groupingid = null) {
	global $CFG;
	$url = $CFG->wwwroot.'/course/report/assessmentpath/report/P1.php?courseid='.$courseid;
	if (!empty($groupingid)) $url .= '&groupingid='.$groupingid;
	if (!empty($userid)) $url .= '&userid='.$userid;
	return $url;
}
function assessmentpath_report_get_link_P1($courseid, $userid = null) {
	global $OUTPUT;
	$reporturl = assessmentpath_report_get_url_P1($courseid, $userid);
	$strreport = get_string('P1', 'assessmentpath');

	// SF2017 - Icons
	//$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'"><img src="'.$OUTPUT->pix_url('i/grades') . '" class="icon" alt="'.$strreport.'" /></a>';
	$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'">'.$OUTPUT->pix_icon('grades', $strreport, 'mod_assessmentpath').'</a>';

	return $reportlink;
}
function assessmentpath_report_get_url_P2($courseid, $groupingid = null) {
	global $CFG;
	$reporturl = $CFG->wwwroot.'/course/report/assessmentpath/report/P2.php?courseid='.$courseid;
	if (!empty($groupingid)) $reporturl .= '&groupingid='.$groupingid;
	return $reporturl;
}
function assessmentpath_report_get_link_P2($courseid, $groupingid = null) {
	global $OUTPUT;
	$reporturl = assessmentpath_report_get_url_P2($courseid, $groupingid);
	$strreport = get_string('P2', 'assessmentpath');

	// SF2017 - Icons
	//$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'"><img src="'.$OUTPUT->pix_url('i/grades') . '" class="icon" alt="'.$strreport.'" /></a>';
	$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'">'.$OUTPUT->pix_icon('grades', $strreport, 'mod_assessmentpath').'</a>';

	return $reportlink;
}
function assessmentpath_report_get_link_P3($activityid) {
	global $CFG, $OUTPUT;
	$reporturl = $CFG->wwwroot.'/mod/assessmentpath/report/P3.php?id='.$activityid;
	$strreport = get_string('P3', 'assessmentpath');

	// SF2017 - Icons
	//$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'"><img src="'.$OUTPUT->pix_url('i/grades') . '" class="icon" alt="'.$strreport.'" /></a>';
	$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'">'.$OUTPUT->pix_icon('grades', $strreport, 'mod_assessmentpath').'</a>';

	return $reportlink;
}
function assessmentpath_report_get_link_P4($stepid) {
	global $CFG, $OUTPUT;
	$reporturl = $CFG->wwwroot.'/mod/assessmentpath/report/P4.php?stepid='.$stepid;
	$strreport = get_string('P4', 'assessmentpath');

	// SF2017 - Icons
	//$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'"><img src="'.$OUTPUT->pix_url('i/grades') . '" class="icon" alt="'.$strreport.'" /></a>';
	$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'">'.$OUTPUT->pix_icon('grades', $strreport, 'mod_assessmentpath').'</a>';

	return $reportlink;
}
function assessmentpath_report_get_link_statistics($scoid, $stepid) {
	global $CFG, $OUTPUT;
	$reporturl = $CFG->wwwroot.'/mod/assessmentpath/report/statistics.php?scoid='.$scoid.'&stepid='.$stepid;
	$strreport = get_string('statistics', 'assessmentpath');
	$reportlink = '<a title="'.$strreport.'" href="'.$reporturl.'">'.$OUTPUT->pix_icon('grades', $strreport, 'mod_assessmentpath').'</a>';
	return $reportlink;
}

// 
// Comments
//

class assessmentpath_comment_form {

	private $commentnb = 0;
	private $edit = true;
	
	public function start($url, $edit = true) {
		$content = '';
		$this->edit = $edit;
		if ($this->edit) { 
			$commentnb	= optional_param('commentnb', 0, PARAM_INT);
			// Main comment
			if ($commentnb > 0) $this->updatecomment('contexttype', 'contextid', 'userid', 'comment');
			// List of additional comments
			for ($i=1; $i < $commentnb; $i++) {
				$this->updatecomment('contexttype'.$i, 'contextid'.$i, 'userid'.$i, 'comment'.$i);
			}
			$content .= '<form action="'.$url.'" method="post" id="commentform">';
		}
		return $content;
	}
	
	public function addcomment($format, $label, $contexttype, $contextid, $userorgroupid = null, $index = null, $css = "comment", $props = 'cols="70" rows="5"') {
		$content = '';
		if ($this->edit) { 
			$content .= '<input type="hidden" name="contexttype'.$index.'" value="'.$contexttype.'" /> ';
			$content .= '<input type="hidden" name="contextid'.$index.'" value="'.$contextid.'" /> ';
			if (isset($userorgroupid)) $content .= '<input type="hidden" name="userid'.$index.'" value="'.$userorgroupid.'" /> ';
		}
		$comment = $this->getcomment($contexttype, $contextid, $userorgroupid);
		if ($this->edit || !empty($comment)) {  // Do not display empty comment if not in editing mode
			if ($format == 'lms' || $format == 'html') {
				$content .= '<div class="'.$css.'">';
				$content .= '<h5>'.$label.'</h5>';
				if ($this->edit) { 
					$content .= '<textarea '.$props.' name="comment'.$index.'">'.$comment.'</textarea>';
				} else {
					$content .= '<p><pre>'.$comment.'</pre></p>';
				}
				$content .= '</div>';
			} else if ($format == 'xls') {
				$content = $comment;
			}
		}
		$this->commentnb += 1;
		return $content;
	}
	
	public function finish() {
		$content = '';
		if ($this->edit) { 
			$content .= '<input type="hidden" name="commentnb" value="'.$this->commentnb.'" /> ';
			$content .= '<div class="commands"><input type="submit" class="btn btn-primary" value="'.get_string('savecomments', 'assessmentpath').'" /></div>';
			$content .= '</form>';
		}
		return $content;
	}
	
	private function getcomment($contexttype, $contextid, $userorgroupid = null) {
		global $DB;
		$cond = array('contexttype'=>$contexttype, 'contextid'=>$contextid);
		if (isset($userorgroupid)) $cond['userid'] = $userorgroupid;
		if ($record = $DB->get_record('assessmentpath_comments', $cond)) {
			return $record->comment;
		} else {
			return '';
		}
	}	
	
	private function updatecomment($contexttype, $contextid, $userorgroupid, $comment) {
		global $DB;
		$obj = new stdClass();
		$obj->contexttype = required_param($contexttype, PARAM_INT);
		$obj->contextid = required_param($contextid, PARAM_INT);
		$obj->userid = optional_param($userorgroupid, null, PARAM_INT);
		$obj->comment = required_param($comment, PARAM_TEXT);
		$cond = array('contexttype'=>$obj->contexttype, 'contextid'=>$obj->contextid);
		if (isset($obj->userid)) $cond['userid'] = $obj->userid;
		if ($record = $DB->get_record('assessmentpath_comments', $cond)) {
			$DB->set_field("assessmentpath_comments", "comment", $obj->comment, $cond);
		} else {
			$DB->insert_record('assessmentpath_comments', $obj);
		}
	}	
}


?>