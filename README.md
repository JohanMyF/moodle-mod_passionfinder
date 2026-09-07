# PassionFinder Activity Module for Moodle

`mod_passionfinder` is a Moodle activity module that helps teachers create reflective **Best–Worst choice** instruments. A PassionFinder activity is made up of:

- teacher-defined categories;
- a prompt for each category;
- teacher-defined items linked to those categories;
- configurable “most” and “least” labels;
- generated choice sets;
- learner responses where exactly one item is selected as most preferred and one item as least preferred;
- relative preference scoring;
- visual browser reports with positive signals and caution signals;
- downloadable PDF reports;
- teacher reports for submitted attempts;
- JSON import and export for sharing instruments between teachers.

PassionFinder is intended for **reflective guidance and structured conversation**, not for clinical, psychometric, diagnostic, selection, or high-stakes assessment. It helps respondents notice patterns in their choices and gives teachers, mentors, coaches, facilitators, or counsellors a better starting point for discussion.

## Why PassionFinder exists

Teachers and facilitators often want learners or respondents to reflect on questions such as:

- What kinds of tasks energise me?
- What kinds of people do I feel drawn to serve or support?
- What work environments feel comfortable or uncomfortable?
- What issues do I feel most strongly about?
- What kinds of roles or activities should I explore further?
- Which work situations should I think about carefully before committing?

A normal Moodle quiz is good at asking questions and marking answers. PassionFinder is different. It asks respondents to make **relative choices**. Instead of rating every item independently, the respondent sees a small group of items and must choose:

- the item that is most preferred, most comfortable, most attractive, most important, or most energising; and
- the item that is least preferred, least comfortable, least attractive, least important, or least energising.

This forces clearer trade-offs than a simple rating scale.

PassionFinder is useful where the purpose is not to give marks, but to help the respondent recognise preference patterns that may otherwise remain vague.

## What kind of tool is PassionFinder?

PassionFinder is a **teacher-authored Best–Worst reflection instrument**. It is inspired by the logic of Best–Worst choice tasks and MaxDiff-style activities, but it does not claim to be a formal psychometric MaxDiff package.

The plugin is designed for educational, pastoral, vocational, coaching, leadership-development, and reflective learning contexts where:

- the teacher controls the wording of categories and items;
- the activity produces structured preference signals;
- the report becomes a discussion document;
- the respondent and facilitator interpret the results together.

A PassionFinder activity should not be used in isolation to make high-stakes decisions.

## Typical use cases

PassionFinder can be used for many low-stakes reflective activities, including:

- career and work-environment reflection;
- ministry-fit or service-fit reflection;
- leadership-role exploration;
- team-role conversations;
- professional development coaching;
- study pathway exploration;
- learner interest discovery;
- course or programme orientation;
- mentoring preparation;
- work preference discussions;
- personal development planning;
- values and issue-priority reflection.

Examples of teacher prompts include:

> The people I feel most and least drawn to serve are...

> The work environments I would find most and least comfortable are...

> The activities that would give me most and least energy are...

> The societal issues I feel most and least passionate about are...

> The organisational cultures I would find most and least sustainable are...

## Teacher workflow

A teacher creates a PassionFinder activity inside a Moodle course.

The teacher can then:

1. Define the activity name and introductory text.
2. Open the PassionFinder Builder.
3. Define the instrument title and description.
4. Choose how many items appear on each screen.
5. Choose how many choice sets are generated per category.
6. Choose the result output size.
7. Define global “most” and “least” labels.
8. Define whether positive and lower/caution signals should appear in visual reports.
9. Choose visual shapes such as hearts and bubbles.
10. Create categories.
11. Add prompts to categories.
12. Add items under each category.
13. Export the completed structure as JSON.
14. Import a JSON file created by another teacher or generated from a valid example.
15. View submitted respondent reports.
16. Download respondent PDF reports.

Example category:

> Work rhythm and structure

Example prompt:

> The work rhythms and structures I would find most and least comfortable are...

Example items:

- Predictable daily routine
- Flexible schedule
- Deadline-driven sprints
- High autonomy
- Rapid task switching

## Student workflow

When a student or respondent opens the activity, the student sees:

- the teacher’s introduction;
- progress information;
- one choice set at a time;
- the category prompt;
- a small list of items;
- one column for the “most” choice;
- one column for the “least” choice.

The student must select exactly one item as the strongest or most preferred option and exactly one item as the weakest or least preferred option.

Progress is saved as the respondent works through the choice sets. The respondent can review earlier sets before final submission.

After submission, the respondent can see a visual report and download a PDF report.

## Results and scoring

PassionFinder calculates results per category.

Each item receives:

- number of appearances;
- number of times selected as most preferred;
- number of times selected as least preferred;
- net score;
- normalised preference score;
- rank within the category.

A simple interpretation is:

| Signal | Meaning |
| --- | --- |
| Positive score | The item was selected as “most” more often than “least”. |
| Zero score | The item showed no strong positive or negative signal in this attempt. |
| Negative score | The item was selected as “least” more often than “most”. |

The visual report uses these signals to create a quick discussion summary.

## Visual reports

PassionFinder can display a browser-based visual report.

The current visual report can show:

- **red hearts** for strongest positive indications;
- **dark caution bubbles** for lower or negative indications;
- a detailed data table below the visual summary.

The visual report is designed to be immediately understandable. Larger shapes indicate stronger relative preference signals.

Example interpretation:

> The respondent showed a strong positive indication for deep individual focus and quiet independent work, while the lower signals suggest that being supervised closely or heavy networking should be discussed carefully before choosing a work environment.

The dark bubbles should not be read as shame, failure, inability, or permanent aversion. They are **fit signals to examine**. They indicate topics that deserve careful discussion.

## PDF reports

PassionFinder includes a dedicated `pdf.php` route that generates a proper Moodle PDF report.

The PDF report includes:

- activity name;
- respondent name;
- course;
- submitted date;
- strongest indications per category;
- fit signals to examine per category;
- data tables;
- educational disclaimer.

The PDF is suitable for download, discussion, mentoring records, or reflective portfolio evidence.

The PDF report is not merely a browser screenshot. It is a structured document generated using Moodle’s PDF library.

## Teacher reports

Teachers with the appropriate capability can open a report page showing submitted respondent attempts.

The teacher report includes:

- student name;
- attempt status;
- progress;
- submitted time;
- action buttons;
- PDF download links.

This allows a teacher, facilitator, mentor, or counsellor to collect respondent reports without logging in as each respondent.

## JSON sharing

A teacher can export the structure of a PassionFinder activity as a JSON file.

The exported JSON includes:

- schema information;
- instrument title and description;
- activity settings;
- global visual-report settings;
- categories;
- category prompts;
- category-level labels;
- report settings;
- items;
- optional item warning text or discussion guidance;
- metadata.

The exported JSON does **not** include:

- student responses;
- student names;
- grades;
- attempts;
- personal data.

This makes it possible for teachers to share PassionFinder instruments with colleagues. A colleague can import the JSON, adapt it in the builder, and use it in another Moodle course.

## AI-assisted authoring pro-tip

PassionFinder becomes especially powerful when a teacher uses AI to create a valid JSON draft.

A teacher can ask ChatGPT or another AI assistant to produce a PassionFinder instrument on a chosen topic, then paste or import the JSON into the PassionFinder Builder. The teacher should then review, edit, and approve every category, prompt, and item before using the activity with respondents.

AI can help draft structure quickly, but the teacher remains responsible for:

- accuracy;
- fairness;
- cultural appropriateness;
- language level;
- age appropriateness;
- ethical suitability;
- avoiding harmful labels;
- avoiding diagnostic claims;
- ensuring that the activity fits the educational purpose.

## Suggested AI prompt for creating a PassionFinder JSON instrument

Teachers can adapt the prompt below.

```text
Create a valid JSON instrument for the Moodle plugin mod_passionfinder.

Purpose:
[INSERT PURPOSE OF THE INSTRUMENT]

Audience:
[INSERT RESPONDENT GROUP]

Context:
[INSERT COURSE, WORKSHOP, COACHING, CAREER, MINISTRY, OR TRAINING CONTEXT]

Create:
- [NUMBER] categories
- [NUMBER] items per category
- clear category ids using lowercase letters, numbers, and underscores
- clear item ids using lowercase letters, numbers, and underscores
- a short prompt for each category
- concise item labels
- optional warning or discussion guidance for each item
- no student data
- no markdown
- no explanation outside the JSON

Use this exact overall structure:

{
  "metadata": {
    "title": "[TITLE]",
    "description": "[DESCRIPTION]",
    "schema": "mod_passionfinder",
    "schemaVersion": 1,
    "createdBy": "ai_draft_review_required",
    "containsStudentData": false,
    "reportNote": "[A short note explaining that this is a reflective tool, not a diagnosis.]"
  },
  "settings": {
    "itemsperround": 5,
    "roundspercategory": 8,
    "resultdepth": 5,
    "mostlabel": "Most drawn to",
    "leastlabel": "Least drawn to",
    "showpositive": true,
    "shownegative": true,
    "positivevisual": "hearts",
    "negativevisual": "bubbles"
  },
  "categories": [
    {
      "id": "example_category",
      "name": "Example category",
      "prompt": "The items I feel most and least drawn to are...",
      "mostlabel": "Most drawn to",
      "leastlabel": "Least drawn to",
      "report": {
        "showpositive": true,
        "shownegative": true,
        "positivevisual": "hearts",
        "negativevisual": "bubbles",
        "showdatatable": true
      },
      "warning": "A short category-level note explaining how lower-scoring items should be discussed carefully.",
      "items": [
        {
          "id": "example_item_1",
          "label": "Example item one",
          "warning": "A short discussion note or caution related to this item."
        },
        {
          "id": "example_item_2",
          "label": "Example item two",
          "warning": "A short discussion note or caution related to this item."
        },
        {
          "id": "example_item_3",
          "label": "Example item three",
          "warning": "A short discussion note or caution related to this item."
        }
      ]
    }
  ]
}

Important requirements:
- Return only valid JSON.
- Do not wrap the JSON in markdown fences.
- Do not add comments.
- Use double quotes for all JSON keys and string values.
- Make sure every category has at least 3 items.
- Prefer 8 to 12 items per category for a useful instrument.
- Keep item labels short enough to display inside visual report shapes.
- Avoid clinical, diagnostic, recruitment-selection, or high-stakes claims.
- Include "containsStudentData": false.
```

## Valid PassionFinder JSON example

The following is a small valid example. It can be used as a template for creating new instruments.

```json
{
  "metadata": {
    "title": "Work Environment Fit Reflection",
    "description": "A reflective instrument that helps respondents explore work environments where they may feel most and least comfortable.",
    "schema": "mod_passionfinder",
    "schemaVersion": 1,
    "createdBy": "example",
    "containsStudentData": false,
    "reportNote": "This is a reflective guidance tool, not a formal career assessment."
  },
  "settings": {
    "itemsperround": 5,
    "roundspercategory": 8,
    "resultdepth": 5,
    "mostlabel": "Most comfortable",
    "leastlabel": "Least comfortable",
    "showpositive": true,
    "shownegative": true,
    "positivevisual": "hearts",
    "negativevisual": "bubbles"
  },
  "categories": [
    {
      "id": "interaction_patterns",
      "name": "Interaction patterns",
      "prompt": "The interaction patterns I would find most and least comfortable are...",
      "mostlabel": "Most comfortable",
      "leastlabel": "Least comfortable",
      "report": {
        "showpositive": true,
        "shownegative": true,
        "positivevisual": "hearts",
        "negativevisual": "bubbles",
        "showdatatable": true
      },
      "warning": "Pay special attention to low-scoring interaction patterns. A mismatch here can affect daily energy and long-term fit.",
      "items": [
        {
          "id": "deep_individual_focus",
          "label": "Deep individual focus",
          "warning": "May suit focused specialist work; be cautious if the role requires constant interruption."
        },
        {
          "id": "small_team_collaboration",
          "label": "Small team collaboration",
          "warning": "May suit project teams; be cautious if team conflict or unclear roles are common."
        },
        {
          "id": "large_group_facilitation",
          "label": "Large group facilitation",
          "warning": "May suit training or leadership; be cautious if public performance drains you."
        },
        {
          "id": "customer_facing_service",
          "label": "Customer-facing service",
          "warning": "May suit service roles; be cautious if emotional labour is frequent."
        },
        {
          "id": "quiet_independent_work",
          "label": "Quiet independent work",
          "warning": "May suit research, writing, or analysis; be cautious if isolation reduces motivation."
        }
      ]
    }
  ]
}
```

## Good item-writing guidance

When writing PassionFinder items:

- use short item labels;
- write one idea per item;
- keep items in the same category logically comparable;
- avoid mixing people, tasks, values, and environments in one category;
- avoid labels that shame the respondent;
- avoid clinical or diagnostic language;
- avoid using protected characteristics as simplistic preference labels;
- use language appropriate to the respondent group;
- make lower-scoring items safe to discuss;
- review AI-generated items before use;
- pilot the instrument with a small group before using it widely.

Better:

> Quiet independent work

Riskier:

> Jobs for people who cannot cope with teams

The second example labels and shames the respondent. PassionFinder works best when items are neutral enough to support honest reflection.

## Design guidance for categories

A good PassionFinder category should contain items that can reasonably be compared with each other.

Good category:

> Work rhythm and structure

Items:

- Predictable daily routine
- Flexible schedule
- Deadline-driven sprints
- High autonomy
- Rapid task switching

Riskier category:

> Things about work

Items:

- Salary
- Children
- Open-plan offices
- Leadership
- Climate change

The second example mixes unrelated dimensions. It may still produce scores, but the interpretation will be weak.

## Interpreting lower or caution signals

Lower-scoring items should not automatically be treated as aversions.

They may mean:

- the respondent actively dislikes the item;
- the respondent is unsure about the item;
- the item was repeatedly less attractive than other options;
- the item should be discussed before choosing a role, environment, or pathway;
- the category needs better wording.

In some contexts, such as work-environment reflection, lower signals can be valuable. For example, a low score for “crisis-response culture” may warn the respondent to ask careful questions before joining a workplace where emergencies are normal.

In other contexts, such as pastoral or service reflection, teachers may prefer to hide lower visual signals in the report. PassionFinder therefore allows positive and lower/caution visual settings to be controlled.

## Installation

1. Copy the `passionfinder` folder into the Moodle `mod` directory.
2. Visit Moodle site administration.
3. Complete the plugin installation.
4. Add a PassionFinder activity to a course.
5. Use the Builder to create categories and items, or import a valid PassionFinder JSON file.

## Upgrade, backup, and restore support

The plugin includes Moodle database install/upgrade support and activity backup/restore support.

When an activity is backed up and restored, the teacher-authored PassionFinder structure is included. Student data handling follows Moodle backup and privacy rules.

## Privacy

PassionFinder stores student attempts, generated choice sets, choices, and calculated results so that the activity can save progress and produce reports.

The plugin implements Moodle’s Privacy API. It supports user data export and deletion through Moodle’s privacy subsystem.

The teacher-exported JSON structure is different from student data export. Teacher JSON export is intended for sharing the activity design only and does not include student responses, student names, grades, or attempts.

## Capabilities

Access is controlled through Moodle capabilities. Typical permissions include:

- viewing the activity;
- submitting a PassionFinder attempt;
- managing the PassionFinder structure;
- viewing reports;
- exporting the teacher-authored JSON structure.

Teachers should configure roles and permissions according to their institutional policies.

## Educational disclaimer

PassionFinder supports structured reflection. It does not create a validated psychological test unless the teacher uses a separately validated instrument and has the right to do so.

The plugin should not be used on its own for:

- clinical diagnosis;
- psychological assessment;
- recruitment selection;
- high-stakes grading;
- labelling learners;
- replacing professional judgement;
- forcing career or placement decisions.

PassionFinder reports should be used as discussion documents. They are most useful when a respondent and facilitator review the patterns together.

## Requirements

The plugin is designed for modern Moodle versions and follows Moodle plugin development expectations, including:

- Moodle language strings for user-facing text;
- capability checks;
- Moodle database API use;
- Moodle event support;
- Privacy API support;
- backup and restore support;
- CSS in plugin `styles.css`;
- AMD JavaScript for browser animation;
- Moodle boilerplate headers in source files.

## Repository naming

Suggested repository name:

```text
moodle-mod_passionfinder
```

The repository root should contain the plugin files directly, for example:

```text
amd/
backup/
classes/
db/
lang/
pix/
builder.php
index.php
lib.php
mod_form.php
pdf.php
report.php
styles.css
version.php
view.php
```

The Moodle installation folder should be:

```text
mod/passionfinder
```

## Suggested screenshots for the Moodle plugin page

Useful screenshots include:

1. learner answering a Best–Worst choice set;
2. teacher using the Instrument Builder;
3. import/export JSON panel;
4. visual report with hearts and caution bubbles;
5. teacher report page listing respondents;
6. downloadable PDF report.

## Suggested mini-course structure

This README can be used as the basis for a short Moodle mini-course.

Suggested sections:

1. What PassionFinder is and is not.
2. Understanding Best–Worst reflection activities.
3. Planning categories and items.
4. Building an instrument in the PassionFinder Builder.
5. Using AI to draft a JSON instrument.
6. Importing and exporting JSON.
7. Running the activity as a respondent.
8. Reading visual reports.
9. Downloading PDF reports.
10. Ethical use and educational disclaimers.

## Licence

This plugin is released under the GNU General Public License v3 or later, consistent with Moodle plugin requirements.

## Author

Developed by Johan Venter as part of a Moodle activity development workflow for reflective learning and reusable teacher-authored instruments.

## Choice-set coverage and learner effort

PassionFinder balances item exposure and pair co-occurrence when generating best-worst choice screens.
For the common configuration of 10 items in a category with 5 items shown per screen, version 0.2.0
uses exact pair-covering designs at key screen counts:

- 6 screens: every item pair appears together at least once.
- 10 screens: every item pair appears together at least twice.
- 14 screens: every item pair appears together at least three times.
- 18 screens: every item pair appears together at least four times.

Intermediate screen counts preserve the guaranteed coverage of the preceding milestone and add further
balanced screens. More screens provide repeated evidence about preferences, but they also increase
respondent burden. For low-stakes reflection, 6 screens per category is therefore a useful starting
point when a category contains exactly 10 items and 5 are shown per screen.

Here, "pair appears together" means that the two items occur in the same best-worst choice set. It does
not mean that the learner makes an explicit binary judgement between every pair. PassionFinder remains
a reflective MaxDiff-style activity rather than a formal psychometric or diagnostic instrument.
