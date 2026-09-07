<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for the PassionFinder activity module.
 *
 * @package    mod_passionfinder
 * @copyright  2026 Johan Venter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'PassionFinder';
$string['modulename'] = 'PassionFinder';
$string['modulenameplural'] = 'PassionFinder activities';
$string['pluginadministration'] = 'PassionFinder administration';

$string['passionfinder:addinstance'] = 'Add a new PassionFinder activity';
$string['passionfinder:view'] = 'View PassionFinder activity';
$string['passionfinder:submit'] = 'Submit PassionFinder choices';
$string['passionfinder:viewreports'] = 'View PassionFinder reports';
$string['passionfinder:manage'] = 'Manage PassionFinder activity';

$string['activitysettings'] = 'Activity settings';
$string['instrumentjson'] = 'Instrument JSON';
$string['instrumentjson_help'] = 'Paste the PassionFinder JSON instrument here. The JSON contains categories, prompts, and item lists.';
$string['itemsperround'] = 'Items per screen';
$string['itemsperround_help'] = 'The number of items shown to the respondent in each best-worst choice screen.';
$string['roundspercategory'] = 'Screens per category';
$string['roundspercategory_help'] = 'The number of best-worst choice screens generated for each category. More screens increase repeated exposure but also increase learner effort. With exactly 10 items and 5 items per screen, 6, 10, 14 and 18 screens guarantee that every item pair appears together at least 1, 2, 3 and 4 times respectively.';
$string['resultdepth'] = 'Result output size';
$string['resultdepth_help'] = 'The number of top-ranked items to show in the final reflective report.';
$string['mostlabel'] = 'Most label';
$string['mostlabel_help'] = 'The label shown above the column where the respondent chooses the item they feel most drawn to.';
$string['leastlabel'] = 'Least label';
$string['leastlabel_help'] = 'The label shown above the column where the respondent chooses the item they feel least drawn to.';

$string['defaultmostlabel'] = 'Most drawn to';
$string['defaultleastlabel'] = 'Least drawn to';

$string['viewplaceholderheading'] = 'PassionFinder is installed';
$string['viewplaceholderbody'] = 'This first build confirms that the activity module installs, stores its setup fields, and displays safely. The attempt engine will be added next.';
$string['jsonvalid'] = 'The JSON appears to be valid.';
$string['jsoninvalid'] = 'The JSON is not valid. Please check the structure and try again.';
$string['privacy:metadata'] = 'The PassionFinder activity module stores respondent choices and calculated reflective results.';
$string['noactivities'] = 'There are no PassionFinder activities in this course.';
$string['name'] = 'Name';
$string['intro'] = 'Description';

$string['parsererroremptyjson'] = 'The instrument JSON is empty.';
$string['parsererrorinvalidjson'] = 'The instrument JSON is not valid JSON: {$a}';
$string['parsererrornocategories'] = 'The instrument must contain at least one category.';
$string['parsererrorinvalidcategory'] = 'Category {$a} is invalid. Each category must have an id, name, prompt, and item list.';
$string['parsererrorduplicatecategory'] = 'The category id "{$a}" is used more than once. Category ids must be unique.';
$string['parsererrornoitems'] = 'The category "{$a}" does not contain any items.';
$string['parsererrorinvaliditem'] = 'An item in category "{$a}" is invalid. Each item must have an id and label.';
$string['parsererrorduplicateitem'] = 'The item id "{$a}" is used more than once within its category. Item ids must be unique inside each category.';
$string['parsererrortoofewitems'] = 'The category "{$a}" has too few items. Each category must contain at least three items.';

$string['noinstrumentjson'] = 'No instrument JSON has been added yet. Edit the activity settings and paste a PassionFinder JSON instrument.';
$string['previewheading'] = 'Generated PassionFinder preview';
$string['previewintro'] = 'The JSON instrument has been parsed successfully. The preview below shows generated best-worst choice sets. These controls are disabled because the attempt engine has not been added yet.';
$string['builderitem'] = 'Item';
$string['builderstatus'] = 'Status';
$string['instrumenttitle'] = 'Instrument title';
$string['categoryplural'] = 'Categories';
$string['builderitems'] = 'Items';
$string['generatedsets'] = 'Generated choice sets';
$string['previewnotattempt'] = 'This is a development preview only. Learner attempts, saving choices, scoring, and reports will be added in the next stages.';
$string['generatedsetspreview'] = 'Choice-set preview';
$string['setnumber'] = 'Set {$a}';
$string['itemlabel'] = 'Item';
$string['previewlimited'] = 'Showing {$a->shown} of {$a->total} generated choice sets.';
$string['parsererrorheading'] = 'The PassionFinder instrument could not be parsed.';

$string['nosetsavailable'] = 'No choice sets are available for this attempt.';
$string['setnotavailable'] = 'The requested choice set is not available.';
$string['attemptlocked'] = 'This attempt has already been submitted and can no longer be changed.';
$string['choiceincomplete'] = 'Please choose one item in each column.';
$string['choicesameitem'] = 'The same item cannot be selected as both Most and Least.';
$string['choiceinvaliditem'] = 'The selected item does not belong to this choice set.';
$string['attemptnotcomplete'] = 'Please answer all choice sets before submitting the attempt.';
$string['choicesaved'] = 'Your choice has been saved.';
$string['attemptcompleted'] = 'Your attempt has been submitted.';
$string['attemptalreadycompleted'] = 'This attempt has already been submitted.';
$string['attemptheading'] = 'PassionFinder attempt';
$string['progresssummary'] = 'Answered: {$a->answered} of {$a->total}. Remaining: {$a->remaining}.';
$string['setnumberoftotal'] = 'Set {$a->number} of {$a->total}';
$string['saveandcontinue'] = 'Save and continue';
$string['savechoice'] = 'Save choice';
$string['previousset'] = 'Previous set';
$string['nextset'] = 'Next set';
$string['reviewheading'] = 'Review your choices';
$string['set'] = 'Set';
$string['category'] = 'Category';
$string['status'] = 'Status';
$string['action'] = 'Action';
$string['answered'] = 'Answered';
$string['notansweredyet'] = 'Not answered yet';
$string['reviewedit'] = 'Review / edit';
$string['submitattempt'] = 'Submit and lock attempt';
$string['submitdisabledhint'] = 'You can submit your attempt after all choice sets have been answered.';

$string['reportheading'] = 'Your PassionFinder report';
$string['reportintro'] = 'This report shows which items you most consistently selected as strongest and weakest indications in each category.';
$string['topindications'] = 'Your strongest indications in this category were: {$a}.';
$string['rank'] = 'Rank';
$string['appearances'] = 'Appearances';
$string['mostcount'] = 'Most';
$string['leastcount'] = 'Least';
$string['netscore'] = 'Net score';
$string['normalisedscore'] = 'Preference score';
$string['reportdisclaimer'] = 'This is not a diagnosis or formal psychometric assessment. It is a structured reflection to support further conversation, discernment, and exploration.';
$string['backtoreview'] = 'Back to choices';

$string['visualreportcategoryintro'] = 'The visual summary highlights the strongest positive indications in this category. Larger shapes indicate stronger relative preference scores.';
$string['visualreportnopositive'] = 'There were no positive indications to show visually in this category.';
$string['visualreportarialabel'] = 'Visual summary of strongest PassionFinder indications.';

$string['printvisualsummary'] = 'Print visual summary';
$string['printdatatable'] = 'Print data table';

$string['instrumentbuilder'] = 'Instrument builder';
$string['buildersaved'] = 'The instrument settings have been saved.';
$string['builderinvalidcategory'] = 'The category is invalid. Please provide a category name, id, and prompt.';
$string['builderduplicatecategory'] = 'A category with this id already exists.';
$string['builderinvaliditem'] = 'The item is invalid. Please provide an item label and id.';
$string['builderduplicateitem'] = 'An item with this id already exists in this category.';
$string['builderjsonvalid'] = 'The current builder content produces valid PassionFinder JSON.';
$string['builderjsonwarning'] = 'The current builder content needs attention: {$a}';
$string['categorysaved'] = 'The category has been saved.';
$string['categoryupdated'] = 'The category has been updated.';
$string['categorydeleted'] = 'The category has been deleted.';
$string['itemsaved'] = 'The item has been saved.';
$string['itemupdated'] = 'The item has been updated.';
$string['itemdeleted'] = 'The item has been deleted.';
$string['categories'] = 'Categories';
$string['nocategoriesyet'] = 'No categories have been added yet.';
$string['editinstrumentsettings'] = 'Edit instrument settings';
$string['instrumentdescription'] = 'Instrument description';
$string['editcategory'] = 'Edit category';
$string['categoryname'] = 'Category name';
$string['categoryid'] = 'Category id';
$string['categoryprompt'] = 'Category prompt';
$string['shownegative'] = 'Show lower indications in report';
$string['noitemsincategory'] = 'There are no items in this category yet.';
$string['itemid'] = 'Item id';
$string['addcategory'] = 'Add category';
$string['additemtocategory'] = 'Add item to {$a}';
$string['deletecategory'] = 'Delete category';
$string['deleteitem'] = 'Delete item';
$string['viewactivity'] = 'View activity';
$string['editsettings'] = 'Edit settings';

$string['buildersettingsintro'] = 'Use the PassionFinder Builder to define categories, prompts, most/least labels, items, and reporting behaviour. The builder saves the instrument as valid PassionFinder JSON behind the scenes.';
$string['buildersettingswarning'] = 'Editing or replacing JSON after respondents have started may affect existing attempts. For live use, create a new activity.';
$string['openinstrumentbuilder'] = 'Open PassionFinder Builder';
$string['builderlinklabel'] = 'Builder';
$string['builderlinkpending'] = 'Save the activity first. After the activity has been created, return to these settings to open the builder.';

$string['importexportjson'] = 'Import / export JSON';
$string['importexportintro'] = 'Export the current instrument as a JSON file, or paste valid PassionFinder JSON to replace the current instrument.';
$string['exportjson'] = 'Export JSON';
$string['importjson'] = 'Import JSON';
$string['pastejson'] = 'Paste PassionFinder JSON';
$string['currentjsonhint'] = 'Importing JSON replaces the current builder content. Export first if you want to keep a backup.';
$string['showcurrentjson'] = 'Show current JSON';
$string['builderimportempty'] = 'No JSON was pasted for import.';
$string['builderimported'] = 'The JSON instrument has been imported successfully.';
$string['builderimportinvalid'] = 'The pasted JSON could not be imported: {$a}';

$string['deletecategoryconfirm'] = 'Delete category "{$a}" and all its items?';
$string['deleteitemconfirm'] = 'Delete this item?';

$string['strongestindications'] = 'Strongest indications';
$string['fitsignalstoexamine'] = 'Fit signals to examine';

$string['visualreportsettings'] = 'Visual report settings';
$string['visualreportsettings_help'] = 'These settings control the default visual summary for this PassionFinder instrument. Category-level settings can be added later.';
$string['showpositivevisual'] = 'Show positive visual signals';
$string['shownegativevisual'] = 'Show lower / caution visual signals';
$string['positivevisual'] = 'Positive visual shape';
$string['negativevisual'] = 'Lower / caution visual shape';
$string['visualhearts'] = 'Hearts';
$string['visualbubbles'] = 'Bubbles';

$string['jsonfile'] = 'JSON file';
$string['builderimportuploaderror'] = 'The JSON file could not be uploaded.';
$string['builderimportfilesize'] = 'The JSON file is empty or too large. Please use a JSON file smaller than 512 KB.';

$string['reports'] = 'Reports';
$string['student'] = 'Student';
$string['attemptstatus'] = 'Attempt status';
$string['progress'] = 'Progress';
$string['timesubmitted'] = 'Submitted';
$string['actions'] = 'Actions';
$string['submitted'] = 'Submitted';
$string['inprogress'] = 'In progress';
$string['answeredsets'] = '{$a->answered} of {$a->total} choice sets answered';
$string['downloadpdf'] = 'Download PDF';
$string['noreportdata'] = 'No report data is available yet.';
$string['resultsnotavailable'] = 'Results are not available yet.';
$string['resultsavailableaftersubmission'] = 'Results are available after submission.';
$string['passionfinderresultreport'] = 'PassionFinder result report';
$string['howtoreadresults'] = 'How to read these results';
$string['pdfreportintro'] = 'This report is a structured reflection aid. Strongest indications show where the respondent most consistently selected items as preferred. Fit signals to examine show lower-scoring items that may deserve careful discussion before making decisions.';
$string['datatable'] = 'Data table';
$string['importantnote'] = 'Important note';
$string['resultdisclaimer'] = 'This report is not a diagnosis or formal psychometric assessment. It is intended to support conversation between the respondent and a teacher, counsellor, coach, facilitator, or mentor. It should not be used on its own to make career, placement, or selection decisions.';

$string['yourresults'] = 'Your results';$string['validateimportjsonfile'] = 'Validate and import JSON file';
$string['validateimportpastedjson'] = 'Validate and import pasted JSON';
$string['advancedjsonintro'] = 'Advanced option: paste, inspect, validate, troubleshoot, or manually save the complete JSON below.';
