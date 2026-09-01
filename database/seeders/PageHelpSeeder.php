<?php

namespace Database\Seeders;

use App\Models\PageHelp;
use Illuminate\Database\Seeder;

class PageHelpSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'dashboard' => [
                'title' => 'Dashboard',
                'content_html' => <<<'HTML'
<p>The Dashboard gives you a quick view of the IRAD work that is most relevant to you. What you see is based on your role and permissions.</p>
<h3>Position Counts</h3>
<p>Use the position summary counts to quickly understand current staffing activity, including positions that are Vacant, Selected, Departing, or On-Hold.</p>
<h3>Position Work</h3>
<p>If you are responsible for positions as a Project Manager or PMO user, your assigned position information appears here so you can identify items that need attention without opening the full Positions list.</p>
<h3>Tickets and Alerts</h3>
<p>Use the ticket and alert areas to review items assigned to you or requiring your attention. Select an item to open its full details.</p>
<h3>Tips</h3>
<ul>
<li>The Dashboard is a summary. Use the navigation menu to open the complete People, Positions, Candidates, Tickets, or other lists.</li>
<li>If a section is not displayed, your account may not have permission to view that information.</li>
<li>Counts and work lists reflect the latest information currently stored in IRAD.</li>
</ul>
HTML,
            ],

            'people.index' => [
                'title' => 'People',
                'content_html' => <<<'HTML'
<p>The People page is the central list of personnel records in IRAD. Use it to find people, review basic information, and open records for additional details.</p>
<h3>Find a Person</h3>
<p>Enter a name, person code, or other visible information in Search and select <strong>Apply</strong>. Select <strong>Reset</strong> to clear the search.</p>
<h3>Sort and Customize the List</h3>
<ul>
<li>Select a sortable column heading to change the sort order.</li>
<li>Use Column Settings to choose which columns are displayed and change their order.</li>
<li>Your saved column preferences are used when you return to the list.</li>
</ul>
<h3>Actions</h3>
<p>Use the Actions menu on a person to View or Edit the record when you have permission. Users with delete permission may also delete records that are not protected by related assignments.</p>
<h3>Export</h3>
<p>Use Export to download the current People data to CSV for reporting or offline review.</p>
HTML,
            ],

            'people.create' => [
                'title' => 'Create Person',
                'content_html' => <<<'HTML'
<p>Use this page to create a new person record and capture the information IRAD needs to manage that person throughout the staffing process.</p>
<h3>Work Through the Sections</h3>
<p>Use the section navigation on the left to move through the form. The sections organize identity and employment details, contact information, organizational assignments, access information, and files.</p>
<h3>Required Information</h3>
<p>At minimum, enter the Person Code, First Name, and Last Name. IRAD will identify missing required information before the record can be saved.</p>
<h3>Contact and Organization Information</h3>
<ul>
<li>Add phone numbers and addresses when available.</li>
<li>Assign the person to the appropriate groups and teams.</li>
<li>Add attachments when supporting files belong with the person record.</li>
</ul>
<h3>Save the Person</h3>
<p>Review the sections for completeness, then select <strong>Create Person</strong>. Select Cancel or Back to List to leave without creating the record.</p>
HTML,
            ],

            'people.show' => [
                'title' => 'Person Details',
                'content_html' => <<<'HTML'
<p>The Person Details page brings together the person's profile, contact information, organizational relationships, assignments, access information, and files.</p>
<h3>Navigate the Record</h3>
<p>Use the section navigation to move between the different parts of the person record without leaving the page.</p>
<h3>Assignments</h3>
<p>Current Assignments shows positions the person is actively filling. Assignment History shows previous and other recorded assignments.</p>
<ul>
<li>Select <strong>Add Assignment</strong> to place the person into a position when you have permission.</li>
<li>Use an assignment's Actions menu to manage or remove that assignment.</li>
</ul>
<h3>Edit</h3>
<p>Select <strong>Edit Person</strong> to update the record when that action is available to you.</p>
<h3>Related Information</h3>
<p>Use the remaining sections to review contact details, group and team membership, access information, and attached files associated with the person.</p>
HTML,
            ],

            'people.edit' => [
                'title' => 'Edit Person',
                'content_html' => <<<'HTML'
<p>Use this page to update an existing person's information. The form is organized into sections so you can change only the information that needs attention.</p>
<h3>Person Details</h3>
<p>Review identity and employment information carefully. Person Code, First Name, and Last Name are required.</p>
<h3>Other Sections</h3>
<ul>
<li>Update phone numbers and addresses as contact information changes.</li>
<li>Maintain group and team assignments.</li>
<li>Review access-related information when applicable.</li>
<li>Add or manage files associated with the person.</li>
</ul>
<h3>Save Changes</h3>
<p>Select <strong>Save Changes</strong> after reviewing your updates. Validation messages will identify information that must be corrected before IRAD can save the record.</p>
<p>Select Cancel or Back to List to leave the page without saving additional changes.</p>
HTML,
            ],

            'positions.index' => [
                'title' => 'Positions',
                'content_html' => <<<'HTML'
<p>The Positions page is the main workspace for finding and managing staffing positions in IRAD.</p>
<h3>Find Positions</h3>
<p>Use Search and the available filters to narrow the list. Search can be used with position codes, job titles, organizations, and other displayed position information.</p>
<h3>Status</h3>
<p>Position status helps identify where a position is in the staffing lifecycle. Common operational states include Vacant, Selected, Departing, and On-Hold.</p>
<h3>Sort and Customize</h3>
<ul>
<li>Select a sortable column heading to sort the list.</li>
<li>Use Column Settings to choose and arrange the columns most useful to your work.</li>
<li>Use Reset when you want to return the filters to their default state.</li>
</ul>
<h3>Actions</h3>
<p>Use the Actions menu to View or Edit a position when permitted. Select Create Position to add a new staffing position.</p>
<h3>Export</h3>
<p>Use Export to download position information to CSV for reporting or offline review.</p>
HTML,
            ],

            'positions.create' => [
                'title' => 'Create Position',
                'content_html' => <<<'HTML'
<p>Use this page to add a staffing position to IRAD and define the information needed to manage it.</p>
<h3>Core Position Information</h3>
<p>Enter the position code and select the appropriate job title. Complete the organizational, project management, funding, and operational information that applies to the position.</p>
<h3>Job Title</h3>
<p>The selected job title provides the position's standard skills, tasks, and requirements. Position-specific requirements can be managed after the position is created.</p>
<h3>Operational Flags</h3>
<p>Review any status, risk, closure, customer-created, or other operational flags carefully. These values affect how the position is represented elsewhere in IRAD.</p>
<h3>Position Summary</h3>
<p>The summary panel updates as you complete the form and provides a quick review of important values before you save.</p>
<h3>Create the Position</h3>
<p>Select <strong>Create Position</strong> when the information is complete. Validation messages will identify required or invalid values.</p>
HTML,
            ],

            'positions.show' => [
                'title' => 'Position Details',
                'content_html' => <<<'HTML'
<p>The Position Details page is the complete view of a staffing position. It combines position information, organizations, staffing activity, candidates, skills, tasks, and history.</p>
<h3>Summary</h3>
<p>The summary area provides quick counts for current assignments, candidates, skills, tasks, and assignment history.</p>
<h3>Position Information</h3>
<p>Review the position code, job title, status, staffing attributes, organizations, funding information, mission description, flags, and other operational details.</p>
<h3>Skills and Tasks</h3>
<ul>
<li>Default skills and tasks come from the assigned job title.</li>
<li>Custom position skills and tasks are requirements added specifically for this position.</li>
<li>Required and Desired items are shown separately where applicable.</li>
</ul>
<h3>Candidates and Assignments</h3>
<p>Use the Candidates section to review people being considered for the position. Current and historical assignments show who fills or previously filled the position.</p>
<h3>Actions</h3>
<p>Select Edit Position to update the position. Use Add Assignment when you need to assign a person and have the required permission.</p>
HTML,
            ],

            'positions.edit' => [
                'title' => 'Edit Position',
                'content_html' => <<<'HTML'
<p>Use this page to maintain a position throughout its staffing lifecycle. The section navigation separates general position data from candidates, skills, tasks, and other position-specific information.</p>
<h3>Position Information</h3>
<p>Update the position code, job title, status, organizations, project manager, funding information, mission information, and operational flags as needed.</p>
<h3>Changing the Job Title</h3>
<p>Job-title requirements provide the default skills and tasks associated with the position. Review the requirements after changing a job title so you understand the defaults that now apply.</p>
<h3>Skills and Tasks</h3>
<p>Use the appropriate sections to review default job-title requirements and manage custom requirements that apply only to this position. Mark items Required or Desired as appropriate.</p>
<h3>Candidates</h3>
<p>Use the Candidates section to review and manage candidates associated with this position.</p>
<h3>Save</h3>
<p>Save your changes after reviewing the affected sections. Validation messages will identify any values that must be corrected.</p>
HTML,
            ],

            'candidates.index' => [
                'title' => 'Candidates',
                'content_html' => <<<'HTML'
<p>The Candidates page tracks people being considered for positions and shows their current staffing and workflow status.</p>
<h3>Find Candidates</h3>
<p>Search by candidate, person, position, or other visible information. Use the Status filter to focus on candidates at a particular point in the process.</p>
<h3>Candidate Status</h3>
<p>Status identifies the candidate's overall placement state. Workflow steps provide the more detailed process used to move the candidate forward.</p>
<h3>Sort and Customize</h3>
<ul>
<li>Select sortable column headings to change the sort order.</li>
<li>Use Column Settings to control which fields appear in the list.</li>
<li>Select Reset to clear the current filters.</li>
</ul>
<h3>Actions</h3>
<p>Use the Actions menu to View or Edit a candidate when you have permission. Select Create Candidate to begin tracking a new candidate for a position.</p>
<h3>Export</h3>
<p>Use Export to download candidate information to CSV.</p>
HTML,
            ],

            'candidates.create' => [
                'title' => 'Create Candidate',
                'content_html' => <<<'HTML'
<p>Use this page to connect a person to a position as a candidate and begin tracking that candidate through the staffing workflow.</p>
<h3>Candidate Details</h3>
<ul>
<li>Select the Person being considered.</li>
<li>Select the Position for which the person is being considered.</li>
<li>Set the appropriate Candidate Status.</li>
<li>Complete Candidate FBR and other candidate-specific information when applicable.</li>
</ul>
<h3>Workflow</h3>
<p>Select the workflow that should be used for this candidate. The workflow determines the steps used to track progress through the process.</p>
<h3>Workflow Steps</h3>
<p>Review the generated workflow steps and update step information as appropriate. These steps become the candidate's detailed progress record.</p>
<h3>Save Candidate</h3>
<p>Select <strong>Save Candidate</strong> after reviewing the person, position, status, and workflow information.</p>
HTML,
            ],

            'candidates.show' => [
                'title' => 'Candidate Details',
                'content_html' => <<<'HTML'
<p>The Candidate Details page shows the complete relationship between a candidate, the linked person, the target position, and the staffing workflow.</p>
<h3>Candidate Information</h3>
<p>Review the candidate code, status, FBR, and other candidate-specific values in the information and quick-summary areas.</p>
<h3>Person and Position</h3>
<p>The Person and Position sections show the records connected to this candidate. Use View Person or View Position to open those records for more detail.</p>
<h3>Workflow Steps</h3>
<p>The Workflow Steps section shows the candidate's progress through the selected process, including the individual steps and their current status.</p>
<h3>Edit</h3>
<p>Select <strong>Edit Candidate</strong> when you need to update candidate information or workflow progress and have the required permission.</p>
HTML,
            ],

            'candidates.edit' => [
                'title' => 'Edit Candidate',
                'content_html' => <<<'HTML'
<p>Use this page to update candidate information and maintain progress through the staffing workflow.</p>
<h3>Candidate Details</h3>
<p>Review the linked Person and Position before making changes. Update the Candidate Status, FBR, or other candidate-specific information when needed.</p>
<h3>Workflow Steps</h3>
<p>Update the workflow steps to reflect the candidate's actual progress. Keep step statuses and related information current so other users can understand where the candidate is in the process.</p>
<h3>Before Saving</h3>
<ul>
<li>Confirm that the correct person and position are associated with the candidate.</li>
<li>Make sure the overall candidate status agrees with the current staffing situation.</li>
<li>Review workflow step changes for accuracy.</li>
</ul>
<p>Select <strong>Update Candidate</strong> to save the changes.</p>
HTML,
            ],

            // Public/Portal versions use separate Inertia component names and therefore
            // need their own help keys even when the underlying workflow is similar.
            'portal.dashboard' => [
                'title' => 'Portal Dashboard',
                'content_html' => <<<'HTML'
<p>The Portal Dashboard is your starting point for IRAD. It summarizes the workforce and staffing information you are authorized to see.</p>
<h3>Position Counts</h3>
<p>Use the Vacant, Selected, Departing, and On-Hold counts to quickly understand the current position workload.</p>
<h3>Your Work</h3>
<p>Depending on your role, the Dashboard may show position information, alerts, tickets, or other items that need your attention.</p>
<h3>Open the Full Record</h3>
<p>Dashboard information is intended as a quick summary. Use the navigation menu or links in the dashboard cards to open the complete record or list.</p>
<p>If you do not see a section that another user sees, the difference may be based on your assigned role or permissions.</p>
HTML,
            ],

            'portal.people.index' => [
                'title' => 'People',
                'content_html' => <<<'HTML'
<p>Use the People page to find personnel records you are authorized to access.</p>
<h3>Search and Review</h3>
<p>Use Search and the available list controls to find a person. Select a record to review the person's details, organizational relationships, and staffing information.</p>
<h3>Available Actions</h3>
<p>The actions displayed depend on your permissions. If you are allowed to create or update people, the appropriate buttons and actions will be available on the page.</p>
<p>Only information you are authorized to access is displayed in the Portal.</p>
HTML,
            ],

            'portal.people.create' => [
                'title' => 'Create Person',
                'content_html' => <<<'HTML'
<p>Use this page to add a person to IRAD when your Portal role allows person creation.</p>
<h3>Complete the Record</h3>
<p>Enter the person's required identity information first, then complete available contact, organizational, and other sections as appropriate.</p>
<p>Review the record before saving. Validation messages will identify required information that is missing or invalid.</p>
HTML,
            ],

            'portal.people.show' => [
                'title' => 'Person Details',
                'content_html' => <<<'HTML'
<p>This page shows the personnel information and staffing relationships you are authorized to view for this person.</p>
<h3>Review the Sections</h3>
<p>Use the page sections to review basic person details, organizational relationships, assignments, contact information, and other available information.</p>
<h3>Assignments</h3>
<p>Current assignments identify positions the person is actively filling. Historical information provides context about prior assignments when available.</p>
<p>If Edit or assignment actions are displayed, you may use them according to your assigned permissions.</p>
HTML,
            ],

            'portal.people.edit' => [
                'title' => 'Edit Person',
                'content_html' => <<<'HTML'
<p>Use this page to update a person record within the permissions assigned to your Portal role.</p>
<p>Review identity, contact, organization, and other available sections and update only information that has changed. Required fields must remain complete.</p>
<p>Select the save action after reviewing your changes. Validation messages will identify anything that must be corrected.</p>
HTML,
            ],

            'portal.positions.index' => [
                'title' => 'Positions',
                'content_html' => <<<'HTML'
<p>Use the Positions page to review staffing positions available to your Portal role.</p>
<h3>Find a Position</h3>
<p>Use Search, filters, sorting, and list controls to narrow the results and locate the position you need.</p>
<h3>Understand Status</h3>
<p>Position status indicates the current staffing condition. Vacant, Selected, Departing, and On-Hold are key states used throughout the Portal.</p>
<h3>Open a Position</h3>
<p>Select a position to review its complete details, including staffing information, candidates, requirements, and assignments when those sections are available to you.</p>
HTML,
            ],

            'portal.positions.create' => [
                'title' => 'Create Position',
                'content_html' => <<<'HTML'
<p>Use this page to create a staffing position when your Portal permissions allow it.</p>
<p>Enter the core position information, select the correct job title and organizations, and complete the operational information that applies to the position.</p>
<p>Review status and other flags carefully because they determine how the position appears in staffing views and dashboard counts.</p>
<p>Select the create action when the position information is complete.</p>
HTML,
            ],

            'portal.positions.show' => [
                'title' => 'Position Details',
                'content_html' => <<<'HTML'
<p>This page provides the detailed view of a staffing position that you are authorized to access.</p>
<h3>Review the Position</h3>
<p>Use the page sections to review the job title, position status, organizations, staffing information, assignments, candidates, skills, tasks, and other operational details.</p>
<h3>Requirements</h3>
<p>Default requirements come from the job title. Custom requirements apply specifically to this position.</p>
<h3>Staffing Activity</h3>
<p>Candidate and assignment sections show who is being considered for the position and who is currently or historically assigned.</p>
<p>Available edit and staffing actions depend on your role and permissions.</p>
HTML,
            ],

            'portal.positions.edit' => [
                'title' => 'Edit Position',
                'content_html' => <<<'HTML'
<p>Use this page to maintain position information when your Portal role allows updates.</p>
<p>Review the position's status, job title, organizations, staffing information, requirements, and operational flags before saving changes.</p>
<p>If you change information that affects staffing status or job-title requirements, review the related candidate, skill, and task sections for consistency.</p>
HTML,
            ],

            'portal.candidates.index' => [
                'title' => 'Candidates',
                'content_html' => <<<'HTML'
<p>Use the Candidates page to review people being considered for positions within your authorized area.</p>
<h3>Find a Candidate</h3>
<p>Use Search, filters, and sorting to locate a candidate by person, position, status, or other available information.</p>
<h3>Track Progress</h3>
<p>The candidate status provides an overall staffing state, while workflow steps provide the detailed progress through the candidate process.</p>
<p>Select a candidate to open the complete record. Create and Edit actions are displayed only when your permissions allow them.</p>
HTML,
            ],

            'portal.candidates.create' => [
                'title' => 'Create Candidate',
                'content_html' => <<<'HTML'
<p>Use this page to begin tracking a person as a candidate for a position.</p>
<p>Select the correct Person and Position, choose the appropriate candidate status, and complete any additional candidate information.</p>
<p>Review the selected workflow and its steps before saving so IRAD begins tracking the candidate with the correct process.</p>
HTML,
            ],

            'portal.candidates.show' => [
                'title' => 'Candidate Details',
                'content_html' => <<<'HTML'
<p>This page shows the candidate's overall status, linked person, target position, and workflow progress.</p>
<h3>Related Records</h3>
<p>Use the Person and Position links to open the related records when those actions are available to you.</p>
<h3>Workflow</h3>
<p>Review Workflow Steps to understand what has been completed, what is currently in progress, and what remains in the candidate process.</p>
<p>Select Edit Candidate when you need to update the record and your role permits changes.</p>
HTML,
            ],

            'portal.candidates.edit' => [
                'title' => 'Edit Candidate',
                'content_html' => <<<'HTML'
<p>Use this page to keep the candidate's staffing and workflow information current.</p>
<p>Confirm the linked person and position, update candidate status or other details as needed, and maintain the workflow steps to reflect actual progress.</p>
<p>Review all changes before saving so the candidate's overall status and workflow remain consistent.</p>
HTML,
            ],
            'portal.jobtitles.index' => [
                'title' => "Job Titles",
                'content_html' => <<<'HTML'
<p>The Job Titles page contains the standard job classifications used by IRAD positions. Each job title can define default skills and tasks that help keep position requirements consistent.</p>
<h3>Find a Job Title</h3>
<p>Use Search, sorting, and Column Settings to locate and organize job titles. Select a job title to review its details and requirements.</p>
<h3>Job Title Requirements</h3>
<p>A job title can include Required Skills, Desired Skills, and Tasks. These serve as the standard requirement template for positions using that title.</p>
<h3>Actions</h3>
<p>Select <strong>Create Job Title</strong> to add a new title. Use the row actions to View or Edit an existing title when your permissions allow it.</p>
<p>Inactive job titles remain in IRAD for historical use but are not available for new position selections.</p>
HTML,
            ],

            'portal.jobtitles.create' => [
                'title' => "Create Job Title",
                'content_html' => <<<'HTML'
<p>Use this page to create a standard job title that can be assigned to positions.</p>
<h3>Job Title Details</h3>
<ul>
<li>Enter a clear Name and useful Description.</li>
<li>Use Sort Order to control where the title appears in lists; lower numbers appear first.</li>
<li>Keep Active enabled when the title should be available for positions.</li>
</ul>
<h3>Requirement Template</h3>
<p>You can optionally clone the skills and tasks from an existing job title. The copied requirements become independent records, so later changes to either job title will not automatically change the other.</p>
<h3>Create</h3>
<p>Review the details and select <strong>Create Job Title</strong>. You can then manage its Required Skills, Desired Skills, and Tasks.</p>
HTML,
            ],

            'portal.jobtitles.show' => [
                'title' => "Job Title Details",
                'content_html' => <<<'HTML'
<p>The Job Title Details page is the main workspace for reviewing a job title and maintaining the standard requirements associated with it.</p>
<h3>Job Title Information</h3>
<p>Review the title, description, active status, and sort order at the top of the record.</p>
<h3>Required and Desired Skills</h3>
<p>Required Skills represent capabilities normally expected for the job title. Desired Skills identify useful capabilities that are preferred but not mandatory.</p>
<h3>Tasks</h3>
<p>Tasks describe standard duties or work associated with the job title.</p>
<h3>Managing Requirements</h3>
<p>Use the Add Skill and Add Task areas to create new requirements. Existing skills and tasks can be maintained from their corresponding sections when your permissions allow it.</p>
<p>These requirements provide defaults for positions that use this job title.</p>
HTML,
            ],

            'portal.jobtitles.edit' => [
                'title' => "Edit Job Title",
                'content_html' => <<<'HTML'
<p>Use this page to update a job title's core information and move to its skills and tasks when requirement changes are needed.</p>
<h3>Job Title Details</h3>
<p>Update the Name, Description, Sort Order, or Active status as needed. Lower sort-order numbers appear earlier in lists.</p>
<h3>Active Status</h3>
<p>Deactivate a job title when it should no longer be available for new positions. Existing records can continue to retain the historical relationship.</p>
<h3>Skills &amp; Tasks</h3>
<p>Use the Skills &amp; Tasks section to manage the default requirements and duties associated with this title.</p>
<p>Select <strong>Update Job Title</strong> to save changes.</p>
HTML,
            ],

            'jobtitlerequirements.index' => [
                'title' => "Job Title Requirements",
                'content_html' => <<<'HTML'
<p>This page provides a central starting point for maintaining the standard requirements attached to job titles.</p>
<h3>Select a Job Title</h3>
<p>Each card represents a job title and summarizes its current skills and tasks. Select the job title you want to maintain.</p>
<h3>What You Can Manage</h3>
<ul>
<li><strong>Required Skills</strong> — skills normally required for the job title.</li>
<li><strong>Desired Skills</strong> — preferred skills that are useful but not mandatory.</li>
<li><strong>Tasks</strong> — standard duties associated with the job title.</li>
</ul>
<p>Changes made to job-title requirements establish the defaults used by positions associated with that title. Position-specific requirements may still be maintained separately.</p>
HTML,
            ],

            'jobtitleskills.index' => [
                'title' => "Job Title Skills",
                'content_html' => <<<'HTML'
<p>Use this page to select a job title and maintain its standard Required and Desired skills.</p>
<h3>Select a Job Title</h3>
<p>Each job-title card shows the title and its current requirement counts. Select <strong>Manage Skills</strong> to open that title's skill section.</p>
<h3>Required vs. Desired</h3>
<p>Use Required for capabilities that are normally necessary for the position. Use Desired for capabilities that strengthen a candidate but are not mandatory.</p>
<p>Keeping these defaults accurate helps position owners and staffing teams evaluate position requirements consistently.</p>
HTML,
            ],

            'jobtitletasks.index' => [
                'title' => "Job Title Tasks",
                'content_html' => <<<'HTML'
<p>Use this page to select a job title and maintain the standard tasks associated with it.</p>
<h3>Select a Job Title</h3>
<p>Each card summarizes a job title and its current requirements. Select <strong>Manage Tasks</strong> to open the task section for that title.</p>
<h3>Task Guidance</h3>
<p>Tasks should describe standard duties or responsibilities that commonly apply to positions using the job title. Keep descriptions clear enough that position owners and candidates understand the expected work.</p>
<p>Position-specific tasks can be added separately when a particular position requires duties beyond the job-title defaults.</p>
HTML,
            ],

            'positionassignments.create' => [
                'title' => "Create Assignment",
                'content_html' => <<<'HTML'
<p>Use this page to assign a person to a position and create the staffing relationship between those records.</p>
<h3>Choose the Records Carefully</h3>
<p>Confirm that you are assigning the correct person to the correct position before saving. Assignment information affects staffing status and the current-assignment views throughout IRAD.</p>
<h3>Assignment Details</h3>
<p>Complete the assignment dates, status, role, or other available assignment information that applies to the person's placement.</p>
<h3>Save</h3>
<p>Select <strong>Create Assignment</strong> when the information is correct. If IRAD identifies a conflict or missing required value, correct the displayed validation messages before trying again.</p>
HTML,
            ],

            'positionassignments.edit' => [
                'title' => "Edit Assignment",
                'content_html' => <<<'HTML'
<p>Use this page to update an existing position assignment as staffing circumstances change.</p>
<h3>Review Before Editing</h3>
<p>Confirm the person and position associated with the assignment before changing dates, status, role, or other assignment information.</p>
<h3>Maintain Accurate History</h3>
<p>When an assignment ends or changes, update the applicable information rather than creating conflicting active assignments. Accurate assignment history supports position status, staffing counts, and reporting.</p>
<p>Select <strong>Update Assignment</strong> to save the changes.</p>
HTML,
            ],

            'workflows.index' => [
                'title' => "Workflows",
                'content_html' => <<<'HTML'
<p>The Workflows page manages the reusable processes IRAD can apply to candidate and staffing activity.</p>
<h3>Find a Workflow</h3>
<p>Use Search, sorting, and the list controls to locate an existing workflow. Select a workflow's actions to edit it when permitted.</p>
<h3>Workflow Purpose</h3>
<p>A workflow defines an ordered set of steps used to track progress through a repeatable process. Keeping workflow definitions consistent allows users to understand where candidates are and what remains to be completed.</p>
<h3>Create a Workflow</h3>
<p>Select <strong>Create Workflow</strong> when a new repeatable process is needed. Avoid creating duplicate workflows when an existing one can be updated or reused.</p>
HTML,
            ],

            'workflows.create' => [
                'title' => "Create Workflow",
                'content_html' => <<<'HTML'
<p>Use this page to define a reusable workflow and the steps users will follow when that workflow is assigned.</p>
<h3>Workflow Details</h3>
<p>Enter a clear workflow name and description so users understand when the process should be used.</p>
<h3>Workflow Steps</h3>
<p>Add the steps in the order they should normally occur. Step names should be concise and describe a meaningful point in the process.</p>
<h3>Before Saving</h3>
<ul>
<li>Confirm that the workflow does not duplicate an existing process.</li>
<li>Review the order of all steps.</li>
<li>Use descriptions where additional guidance will help users understand the process.</li>
</ul>
<p>Select <strong>Create Workflow</strong> when the definition is complete.</p>
HTML,
            ],

            'workflows.edit' => [
                'title' => "Edit Workflow",
                'content_html' => <<<'HTML'
<p>Use this page to maintain a workflow definition and its ordered steps.</p>
<h3>Workflow Details</h3>
<p>Update the workflow name or description when the purpose of the process needs clarification.</p>
<h3>Workflow Steps</h3>
<p>Add, remove, rename, or reorder steps carefully. Workflow definitions may be used to guide active candidate processes, so changes should preserve a clear and understandable progression.</p>
<h3>Review Changes</h3>
<p>Before saving, confirm that the step order reflects the actual process users should follow. Select <strong>Update Workflow</strong> to save the revised definition.</p>
HTML,
            ],

            'portal.tickets.index' => [
                'title' => "My Support Tickets",
                'content_html' => <<<'HTML'
<p>My Support Tickets shows the requests you have submitted to the IRAD support team and lets you follow their progress.</p>
<h3>Find a Ticket</h3>
<p>Use Search to find a request by ticket number, title, or description. Select Reset to clear the search.</p>
<h3>Review Status</h3>
<p>Each ticket shows its current status and other available tracking information. Open a ticket to review the original request, activity, and any resolution information.</p>
<h3>Submit a New Request</h3>
<p>Select <strong>Submit a Request</strong> when you need help with IRAD, encounter a problem, or want to report something that needs attention.</p>
HTML,
            ],

            'portal.tickets.create' => [
                'title' => "Submit a Support Request",
                'content_html' => <<<'HTML'
<p>Use this page to send an IRAD support request when you encounter a problem, need assistance, or want to report an improvement opportunity.</p>
<h3>Describe the Request</h3>
<ul>
<li>Give the request a short, meaningful title.</li>
<li>Include the page URL when the issue relates to a specific IRAD screen.</li>
<li>Explain what happened, what you expected, and any steps that may help reproduce the issue.</li>
<li>Add a screenshot when it will help support understand the problem.</li>
</ul>
<h3>Before Submitting</h3>
<p>Do not include passwords or other sensitive authentication information in the request. Select <strong>Submit Request</strong> when the description provides enough information for support to begin working the issue.</p>
HTML,
            ],

            'portal.tickets.show' => [
                'title' => "Support Ticket Details",
                'content_html' => <<<'HTML'
<p>This page shows the complete history and current status of one of your support requests.</p>
<h3>Request Details</h3>
<p>Review the ticket number, status, description, related page information, and screenshot when one was included.</p>
<h3>Activity</h3>
<p>The Activity section shows updates made as the support request is reviewed and worked.</p>
<h3>Resolution</h3>
<p>When the issue is resolved, the Resolution section provides the available outcome or explanation.</p>
<p>Select <strong>Return to Tickets</strong> to go back to your complete support-request list.</p>
HTML,
            ],

            'portal.alerts.index' => [
                'title' => "Alerts",
                'content_html' => <<<'HTML'
<p>The Alerts page shows IRAD notifications that require your awareness or may need your attention.</p>
<h3>Filter Alerts</h3>
<p>Use All, Unread, and Read to focus the list. The counts show how many alerts are currently available in each state.</p>
<h3>Mark Alerts Read</h3>
<p>Select <strong>Read</strong> on an individual alert after reviewing it, or use <strong>Mark all read</strong> when you have reviewed all unread alerts.</p>
<h3>Open Related Work</h3>
<p>When an alert includes a link to a person, position, candidate, ticket, or other IRAD record, use that link to open the related work.</p>
<p>Marking an alert read does not change the underlying record; it only changes your notification state.</p>
HTML,
            ],

            'admin.index' => [
                'title' => 'Admin Portal',
                'content_html' => <<<'HTML'
<p>The Admin Portal is the central workspace for configuring IRAD, managing access, maintaining reference data, and supporting users.</p>
<h3>Find an Admin Tool</h3>
<p>Use the admin search to quickly locate modules, pages, and actions. You can also browse the grouped administration areas shown on the page.</p>
<h3>Access Is Permission-Based</h3>
<p>You will only see administrative tools your account is permitted to use. If a tool you expect is missing, contact an administrator who manages roles and permissions.</p>
<h3>Before Making Changes</h3>
<p>Administrative changes can affect many IRAD users. Review the record and understand the impact before changing permissions, site settings, organizational structure, or shared content.</p>
HTML,
            ],

            'admin.organizations.index' => [
                'title' => 'Manage Organizations',
                'content_html' => <<<'HTML'
<p>Organizations are the highest-level organizational records used to group IRAD people, positions, and related structures.</p>
<h3>Find an Organization</h3>
<p>Use Search, sorting, and Column Settings to locate and organize records. Select an organization to edit it when your permissions allow.</p>
<h3>Keep the Structure Current</h3>
<p>Organization records are used throughout IRAD. Avoid creating duplicates, and deactivate or update records when the organizational structure changes.</p>
<h3>Actions</h3>
<p>Select <strong>Create Organization</strong> to add a new organization. Use the row actions to edit an existing record.</p>
HTML,
            ],

            'admin.organizations.create' => [
                'title' => 'Create Organization',
                'content_html' => <<<'HTML'
<p>Use this page to add an organization to IRAD.</p>
<h3>Organization Details</h3>
<p>Enter the official organization name and any other requested identifying or descriptive information. Use a clear, consistent naming convention so the organization is easy to recognize throughout IRAD.</p>
<h3>Before Creating</h3>
<p>Check the existing Organizations list first to avoid creating a duplicate record.</p>
<p>Select <strong>Create Organization</strong> after reviewing the information.</p>
HTML,
            ],

            'admin.organizations.edit' => [
                'title' => 'Edit Organization',
                'content_html' => <<<'HTML'
<p>Use this page to update an existing organization.</p>
<h3>Review the Impact</h3>
<p>Organizations can be referenced by groups, teams, people, positions, and other records. Renaming or changing an organization may affect how those records appear throughout IRAD.</p>
<h3>Maintain the Record</h3>
<p>Update the organization information as needed and preserve the existing record whenever possible instead of creating a replacement for the same organization.</p>
<p>Select <strong>Update Organization</strong> to save your changes.</p>
HTML,
            ],

            'admin.groups.index' => [
                'title' => 'Manage Groups',
                'content_html' => <<<'HTML'
<p>Groups organize related teams and records within the IRAD organizational structure.</p>
<h3>Find a Group</h3>
<p>Use Search, sorting, filters, and Column Settings to locate a group. The list shows the organizational relationship and other available group information.</p>
<h3>Maintain Relationships</h3>
<p>Make sure each group is associated with the correct organization so downstream team, people, and position information remains accurate.</p>
<h3>Actions</h3>
<p>Select <strong>Create Group</strong> to add a new group, or use the row actions to edit an existing one.</p>
HTML,
            ],

            'admin.groups.create' => [
                'title' => 'Create Group',
                'content_html' => <<<'HTML'
<p>Use this page to add a group to the IRAD organizational structure.</p>
<h3>Choose the Organization</h3>
<p>Associate the group with the correct parent organization. This relationship determines where the group appears throughout IRAD.</p>
<h3>Group Details</h3>
<p>Enter a clear name and complete the available descriptive or status information. Check for an existing group before creating a duplicate.</p>
<p>Select <strong>Create Group</strong> when the information is correct.</p>
HTML,
            ],

            'admin.groups.edit' => [
                'title' => 'Edit Group',
                'content_html' => <<<'HTML'
<p>Use this page to update a group's information or organizational relationship.</p>
<h3>Parent Organization</h3>
<p>Changing the parent organization can affect the organizational context of teams and other records associated with this group. Confirm the change before saving.</p>
<h3>Maintain the Existing Record</h3>
<p>Update the existing group whenever it represents the same organizational entity rather than creating a duplicate replacement.</p>
<p>Select <strong>Update Group</strong> to save the changes.</p>
HTML,
            ],

            'admin.teams.index' => [
                'title' => 'Manage Teams',
                'content_html' => <<<'HTML'
<p>Teams represent the working-level organizational units used by people, positions, and related IRAD records.</p>
<h3>Find a Team</h3>
<p>Use Search, sorting, filters, and Column Settings to locate a team and review its organization and group relationships.</p>
<h3>Keep Hierarchy Accurate</h3>
<p>Teams should be attached to the correct group and organization. Accurate relationships improve filtering, reporting, and assignment throughout IRAD.</p>
<h3>Actions</h3>
<p>Select <strong>Create Team</strong> to add a new team or use the available row actions to edit an existing team.</p>
HTML,
            ],

            'admin.teams.create' => [
                'title' => 'Create Team',
                'content_html' => <<<'HTML'
<p>Use this page to create a team within the IRAD organizational hierarchy.</p>
<h3>Select the Correct Parent</h3>
<p>Choose the appropriate organization and group before creating the team. These relationships determine where the team belongs.</p>
<h3>Team Details</h3>
<p>Enter a clear team name and complete any available status or descriptive fields. Check the existing team list first to avoid duplicates.</p>
<p>Select <strong>Create Team</strong> after reviewing the information.</p>
HTML,
            ],

            'admin.teams.edit' => [
                'title' => 'Edit Team',
                'content_html' => <<<'HTML'
<p>Use this page to update a team's name, status, or organizational relationship.</p>
<h3>Changing Organizational Placement</h3>
<p>If you move a team to a different group or organization, review the effect on people and positions already associated with that team.</p>
<h3>Preserve History</h3>
<p>When possible, update the existing team rather than creating a duplicate record for the same organizational unit.</p>
<p>Select <strong>Update Team</strong> to save your changes.</p>
HTML,
            ],

            'admin.users.index' => [
                'title' => 'Manage Users',
                'content_html' => <<<'HTML'
<p>The Users page provides administrative oversight of IRAD user accounts and their access.</p>
<h3>Find a User</h3>
<p>Use Search, sorting, filters, and Column Settings to locate an account. Review the available role, status, and account information before making changes.</p>
<h3>Permissions</h3>
<p>User access is primarily controlled through roles and permissions. Use the permission-management action when a user's access needs to be reviewed or adjusted.</p>
<h3>Use Care with Access Changes</h3>
<p>Grant only the access required for the user's responsibilities. Removing permissions can immediately affect which IRAD pages and actions the user can access.</p>
HTML,
            ],

            'admin.users.editpermissions' => [
                'title' => 'Edit User Permissions',
                'content_html' => <<<'HTML'
<p>Use this page to review and adjust the access available to a specific IRAD user.</p>
<h3>Roles and Permissions</h3>
<p>Roles provide reusable groups of permissions. Direct permissions apply specifically to this user. Prefer role-based access when multiple users need the same capabilities.</p>
<h3>Least Required Access</h3>
<p>Only grant permissions the user needs to perform assigned responsibilities. Administrative and security-related permissions should be assigned carefully.</p>
<h3>Before Saving</h3>
<p>Review the complete effective access, not just the permissions you changed. Select the save action only after confirming the user should receive the resulting level of access.</p>
HTML,
            ],

            'admin.roles.index' => [
                'title' => 'Manage Roles',
                'content_html' => <<<'HTML'
<p>Roles are reusable collections of permissions that make IRAD access easier to manage consistently.</p>
<h3>Use Roles for Common Responsibilities</h3>
<p>Create or maintain roles around real job responsibilities, such as administrators or workflow-specific users, rather than assigning large numbers of direct permissions to individuals.</p>
<h3>Review Before Editing</h3>
<p>Changes to a role affect every user assigned to that role. Review current usage before adding or removing permissions.</p>
<h3>Actions</h3>
<p>Select <strong>Create Role</strong> to define a new access profile, or edit an existing role when its responsibilities change.</p>
HTML,
            ],

            'admin.roles.create' => [
                'title' => 'Create Role',
                'content_html' => <<<'HTML'
<p>Use this page to create a reusable IRAD access role.</p>
<h3>Name the Role Clearly</h3>
<p>Choose a name that reflects the responsibility or job function the role represents.</p>
<h3>Select Permissions</h3>
<p>Assign only the permissions required for that responsibility. Avoid broad administrative permissions unless the role is intended for administrators.</p>
<h3>Review the Complete Role</h3>
<p>Before saving, review every selected permission because users assigned to the role will receive that access.</p>
HTML,
            ],

            'admin.roles.edit' => [
                'title' => 'Edit Role',
                'content_html' => <<<'HTML'
<p>Use this page to update a role and the permissions it grants.</p>
<h3>Changes Affect Multiple Users</h3>
<p>A role may be assigned to many users. Adding a permission expands access for all of them; removing one may prevent them from completing existing responsibilities.</p>
<h3>Keep Roles Focused</h3>
<p>Roles are easier to manage when they represent a clear responsibility instead of accumulating unrelated permissions over time.</p>
<p>Review the complete permission set before saving your changes.</p>
HTML,
            ],

            'admin.permissions.index' => [
                'title' => 'Manage Permissions',
                'content_html' => <<<'HTML'
<p>Permissions are the individual access controls used by IRAD roles and user accounts.</p>
<h3>Permission Purpose</h3>
<p>Each permission should represent a specific capability, such as viewing a module, managing records, or performing an administrative action.</p>
<h3>Use Care When Changing Permissions</h3>
<p>Permission names may be referenced by application code and roles. Changing or removing an existing permission can affect access throughout IRAD.</p>
<h3>Actions</h3>
<p>Create a new permission only when a new application capability requires one. Edit existing permissions cautiously and avoid duplicate permissions with overlapping purposes.</p>
HTML,
            ],

            'admin.permissions.create' => [
                'title' => 'Create Permission',
                'content_html' => <<<'HTML'
<p>Use this page to define a new IRAD permission.</p>
<h3>Before Creating</h3>
<p>Confirm that an equivalent permission does not already exist and that the application feature actually uses the new permission.</p>
<h3>Naming</h3>
<p>Follow the existing permission naming convention so the permission remains understandable to administrators and consistent with application authorization rules.</p>
<p>After creating the permission, assign it to the appropriate role or user only when needed.</p>
HTML,
            ],

            'admin.permissions.edit' => [
                'title' => 'Edit Permission',
                'content_html' => <<<'HTML'
<p>Use this page to modify an existing permission.</p>
<h3>Use Extreme Care</h3>
<p>Permissions may be referenced directly by application authorization code and may be assigned to multiple roles and users. Renaming a permission without corresponding application changes can break access.</p>
<h3>Before Saving</h3>
<p>Confirm why the change is necessary and understand which roles and users depend on the permission. Avoid changing established permission identifiers for display-only reasons.</p>
HTML,
            ],

            'admin.tickets.index' => [
                'title' => 'Support Ticket Administration',
                'content_html' => <<<'HTML'
<p>The Support Tickets page is the administrative workspace for reviewing and managing requests submitted by IRAD users.</p>
<h3>Find Tickets</h3>
<p>Use Search, status filters, sorting, and the list controls to focus on the requests that need attention.</p>
<h3>Prioritize Work</h3>
<p>Review the ticket description, current status, submitter, and available page or screenshot information. Open a ticket to investigate and record progress.</p>
<h3>Keep Status Current</h3>
<p>Update ticket status as work progresses so the submitting user and other administrators can see an accurate picture of the request.</p>
HTML,
            ],

            'admin.tickets.show' => [
                'title' => 'Manage Support Ticket',
                'content_html' => <<<'HTML'
<p>This page contains the full support request and the administrative tools used to work it through resolution.</p>
<h3>Review the Request</h3>
<p>Read the description, related page information, screenshot, submitter details, and prior activity before making changes.</p>
<h3>Record Progress</h3>
<p>Use the available activity, status, assignment, and resolution fields to document what has been done and what remains.</p>
<h3>Resolution</h3>
<p>When closing a ticket, provide enough resolution information that the submitter and future administrators can understand the outcome.</p>
HTML,
            ],

            'admin.sitesettings.index' => [
                'title' => 'Site Settings',
                'content_html' => <<<'HTML'
<p>Site Settings controls configurable IRAD branding and portal presentation without requiring a code change.</p>
<h3>What You Can Manage</h3>
<p>Depending on the available settings, administrators can maintain items such as logos, colors, site text, and other configurable portal presentation values.</p>
<h3>Review Before Saving</h3>
<p>Site-setting changes can affect the experience for all users. Verify text, branding, and visual settings before publishing changes.</p>
<h3>Use Configurable Settings When Available</h3>
<p>When a supported Site Setting exists, update it here instead of requesting a code change for the same presentation value.</p>
HTML,
            ],

            'admin.pagehelp.index' => [
                'title' => 'Page Help Administration',
                'content_html' => <<<'HTML'
<p>Page Help Administration manages the contextual Help content displayed from IRAD's Help panel.</p>
<h3>Help Keys</h3>
<p>Each Help page is connected to an IRAD screen through its Help Key. Most keys are automatically derived from the Vue page name, so changing a key can disconnect the Help content from its page.</p>
<h3>Import and Export</h3>
<p>Use <strong>Export Help</strong> to download a portable JSON backup of all Help content. Use <strong>Import Help</strong> to restore or move that content between environments. Import updates matching records by Help Key and creates missing records without deleting unrelated Help pages.</p>
<h3>Maintain Help Content</h3>
<p>Keep Help practical and task-focused. Explain what the page is for, the major actions users can take, and important workflow or permission considerations.</p>
HTML,
            ],

            'admin.pagehelp.create' => [
                'title' => 'Create Help Page',
                'content_html' => <<<'HTML'
<p>Use this page to create contextual Help content for an IRAD screen.</p>
<h3>Help Key</h3>
<p>The Help Key must match the key requested by the target screen. When you arrive here from a page with missing Help, IRAD may prefill the correct key for you.</p>
<h3>Title and Content</h3>
<p>Give the Help page a clear title and write concise, user-focused guidance. Organize longer content with headings, short paragraphs, and lists.</p>
<h3>Active Status</h3>
<p>Keep the page active when it should be available from the Help panel. Inactive Help records remain stored but are not shown to users.</p>
HTML,
            ],

            'admin.pagehelp.edit' => [
                'title' => 'Edit Help Page',
                'content_html' => <<<'HTML'
<p>Use this page to revise existing contextual Help content.</p>
<h3>Preserve the Help Key</h3>
<p>Changing the Help Key can disconnect the content from the IRAD page that currently uses it. Change the key only when the target page key has also changed.</p>
<h3>Improve the Guidance</h3>
<p>Keep instructions aligned with the current application. Update Help whenever workflows, labels, page actions, or permission behavior change.</p>
<h3>Back Up Important Changes</h3>
<p>After significant Help updates, use Export Help from the Page Help list to create a current portable JSON backup.</p>
HTML,
            ],

            'admin.contentpages.index' => [
                'title' => 'Content Pages',
                'content_html' => <<<'HTML'
<p>Content Pages lets administrators maintain configurable public or portal informational pages without changing application code.</p>
<h3>Find a Page</h3>
<p>Use Search and the list controls to locate an existing content page. Review its title, status, location, and other available publishing information.</p>
<h3>Manage Carefully</h3>
<p>Content pages may appear in navigation or be linked from other areas of IRAD. Before changing a slug, navigation setting, or active status, consider how users currently reach the page.</p>
<h3>Actions</h3>
<p>Select <strong>Create Content Page</strong> to add a page, or edit an existing page to maintain its content and presentation.</p>
HTML,
            ],

            'admin.contentpages.create' => [
                'title' => 'Create Content Page',
                'content_html' => <<<'HTML'
<p>Use this page to create configurable informational content for the IRAD portal.</p>
<h3>Page Details</h3>
<p>Enter a clear title, appropriate slug or page identifier, and the content users should see. Configure navigation and active settings according to where the page belongs.</p>
<h3>Help Integration</h3>
<p>If the content page should have contextual Help, assign the appropriate Help Key and make sure a matching Page Help record exists.</p>
<h3>Before Publishing</h3>
<p>Review the content, navigation placement, and visibility settings before making the page active.</p>
HTML,
            ],

            'admin.contentpages.edit' => [
                'title' => 'Edit Content Page',
                'content_html' => <<<'HTML'
<p>Use this page to update a configurable IRAD content page.</p>
<h3>Content and Navigation</h3>
<p>Maintain the page text and presentation while preserving navigation behavior users already rely on. Changing a slug or location can affect existing links.</p>
<h3>Visibility</h3>
<p>Use the active and navigation settings to control whether the page is available and where users can discover it.</p>
<h3>Help Key</h3>
<p>If the page uses contextual Help, keep its Help Key synchronized with the intended Page Help record.</p>
HTML,
            ],

            'admin.impersonation.index' => [
                'title' => 'User Impersonation',
                'content_html' => <<<'HTML'
<p>User Impersonation lets authorized administrators temporarily view IRAD as another eligible user for support and troubleshooting.</p>
<h3>Use for Support</h3>
<p>Search for the user whose experience you need to verify, then begin impersonation only when it is necessary to diagnose an access or application issue.</p>
<h3>Security</h3>
<p>Impersonation does not grant permission to bypass protected or sensitive actions. Use it only for legitimate administrative support purposes.</p>
<h3>Return to Your Account</h3>
<p>When troubleshooting is complete, end impersonation promptly and confirm that you have returned to your own administrator session.</p>
HTML,
            ],

            'admin.componentshowcase' => [
                'title' => 'Component Showcase',
                'content_html' => <<<'HTML'
<p>The Component Showcase is an administrative reference for reviewing reusable IRAD interface components and visual patterns.</p>
<h3>Purpose</h3>
<p>Use this page to compare common controls, layout patterns, states, and component behavior so new IRAD screens remain visually and functionally consistent.</p>
<h3>Development Reference</h3>
<p>This page is primarily useful when evaluating interface consistency or planning application changes. It does not modify production records.</p>
HTML,
            ],

            'public.home' => [
                'title' => 'IRAD Portal Home',
                'content_html' => <<<'HTML'
<p>The IRAD Portal home page is the starting point for program information, resources, contacts, support, and authenticated portal tools.</p>
<h3>Explore Program Information</h3>
<p>Use the page sections and main navigation to find program details, contacts, resources, frequently asked questions, and other published information.</p>
<h3>Open the Portal</h3>
<p>If IRAD has already identified you as an authorized user, use the primary portal action to open your dashboard and role-appropriate tools.</p>
<h3>Need Help?</h3>
<p>Use the Support area when you need assistance with IRAD or need to report an issue. The Help button provides guidance for the page you are currently viewing.</p>
HTML,
            ],

            'content.program-overview' => [
                'title' => 'Program Overview',
                'content_html' => <<<'HTML'
<p>The Program Overview explains the purpose of the IRAD Portal and the information and services available to the program community.</p>
<h3>Use This Page To</h3>
<ul>
<li>Understand the purpose of the portal.</li>
<li>Learn what types of program information and operational tools are available.</li>
<li>Identify where to go next for contacts, resources, workforce information, or support.</li>
</ul>
<p>Use the main portal navigation to move to the area that best matches what you need to accomplish.</p>
HTML,
            ],

            'content.program-contacts' => [
                'title' => 'Program Contacts',
                'content_html' => <<<'HTML'
<p>Program Contacts provides the current points of contact for program leadership, operations, contracting, PMO support, and other published responsibilities.</p>
<h3>Choose the Right Contact</h3>
<p>Review the listed role or responsibility before reaching out so your question is directed to the appropriate person or team.</p>
<h3>Keep Contact Information Current</h3>
<p>If you notice outdated contact information, submit a support request or contact the appropriate administrator so the published information can be corrected.</p>
HTML,
            ],

            'content.resources' => [
                'title' => 'Program Resources',
                'content_html' => <<<'HTML'
<p>The Resources page brings together frequently used program links, documentation, forms, templates, policies, and reference material.</p>
<h3>Finding a Resource</h3>
<p>Review the available sections and descriptions to locate the resource you need. Links may open another IRAD page, a shared repository, or an approved external system.</p>
<h3>Missing or Outdated Resources</h3>
<p>If an important resource is missing or a link no longer works, submit a support request so the resource library can be updated.</p>
HTML,
            ],

            'content.faqs' => [
                'title' => 'Frequently Asked Questions',
                'content_html' => <<<'HTML'
<p>The Frequently Asked Questions page provides quick answers to common questions about the program and IRAD Portal.</p>
<h3>View an Answer</h3>
<p>Select a question to expand its answer. Select it again to collapse the answer when you are finished.</p>
<h3>Still Need Help?</h3>
<p>If your question is not covered here, use the Support area to submit a request. Frequently asked support questions may be added to this page over time.</p>
HTML,
            ],

            'content.policies-documentation' => [
                'title' => 'Policies and Documentation',
                'content_html' => <<<'HTML'
<p>This page provides approved program policies, procedures, and authoritative documentation made available through IRAD.</p>
<h3>Use the Current Version</h3>
<p>Review document titles, dates, and other available version information before relying on a policy or procedure.</p>
<h3>Questions About a Policy</h3>
<p>Use the identified program contact when the document provides one. For portal access or document-link problems, submit an IRAD support request.</p>
HTML,
            ],

            'content.announcements' => [
                'title' => 'Program Announcements',
                'content_html' => <<<'HTML'
<p>Program Announcements contains current notices and time-sensitive information published for the IRAD community.</p>
<h3>Review Current Notices</h3>
<p>Pay attention to dates, deadlines, affected groups, and any requested actions included in an announcement.</p>
<h3>Expired Announcements</h3>
<p>Announcements may automatically stop displaying after their configured availability period. Contact the appropriate program office if you need information from an older notice.</p>
HTML,
            ],

            'public.contentpages.show' => [
                'title' => 'Program Information',
                'content_html' => <<<'HTML'
<p>This page contains program information published through IRAD.</p>
<h3>Using the Page</h3>
<p>Review the title, summary, and page content for the information you need. Some pages may include expandable questions, resource links, contacts, policies, or announcements.</p>
<h3>Need More Information?</h3>
<p>Use the main portal navigation to explore related program content or submit a support request when you need assistance.</p>
HTML,
            ],

            'tickets.create' => [
                'title' => 'Submit a Request',
                'content_html' => <<<'HTML'
<p>Use this page to report an IRAD problem or request an improvement.</p>
<h3>Describe What You Need</h3>
<ul>
<li>Enter a short, meaningful title.</li>
<li>Select the request type, importance, and category that best describe the issue.</li>
<li>Explain what happened, what you expected, and any useful steps for reproducing the problem.</li>
<li>Keep the Source URL when the request relates to the page you came from.</li>
<li>Add a screenshot when it will help explain the issue.</li>
</ul>
<h3>Before Submitting</h3>
<p>Do not include passwords or sensitive authentication information. Provide enough detail that support can begin investigating without having to guess what occurred.</p>
HTML,
            ],

            'settings.profile' => [
                'title' => 'Profile Settings',
                'content_html' => <<<'HTML'
<p>Profile Settings lets you review and maintain the basic account information associated with your IRAD user profile.</p>
<h3>Profile Information</h3>
<p>Update your name or email address when those values are editable for your account. Some account information may be supplied by IRAD's identity mechanism and may not be intended for manual changes.</p>
<h3>Email Verification</h3>
<p>If email verification is required and your address is not verified, use the available verification action to request a new verification message.</p>
<h3>Save Changes</h3>
<p>Review your information before saving. Changes to account identity information may affect notifications or how your name appears within IRAD.</p>
HTML,
            ],

            'settings.appearance' => [
                'title' => 'Appearance Settings',
                'content_html' => <<<'HTML'
<p>Appearance Settings controls how IRAD is displayed for your account.</p>
<h3>Choose Your Preference</h3>
<p>Select the available light, dark, or system-based appearance option that works best for you. A system-based option follows the appearance preference configured on your device or browser.</p>
<h3>Personal Setting</h3>
<p>Changing appearance affects your own IRAD experience and does not change the site-wide branding configured by administrators.</p>
HTML,
            ],

            'settings.security' => [
                'title' => 'Security Settings',
                'content_html' => <<<'HTML'
<p>Security Settings contains the account-security controls available to your IRAD user profile.</p>
<h3>Password</h3>
<p>When password management is available for your account, use a long, unique password and do not reuse credentials from other systems.</p>
<h3>Two-Factor Authentication</h3>
<p>If two-factor authentication is available, follow the setup process carefully and store recovery codes in a secure location separate from the device used for authentication.</p>
<h3>Identity-Managed Accounts</h3>
<p>Some IRAD accounts are resolved through the existing identity environment. In those cases, certain password or authentication controls may be managed outside IRAD and may not be available here.</p>
HTML,
            ],

            'admin.data-imports.index' => [
                'title' => 'Data Import',
                'content_html' => <<<'HTML'
<p>Data Import lets authorized administrators load staffing information from Excel while reviewing mappings, validation results, and existing-record conflicts before any staffing data changes.</p>
<h3>Start With a Template</h3>
<p>Select a Candidate Workflow and download the Insight Excel template when you need a clean workbook to populate. The template reflects the current importable fields, active custom fields, workflow steps, and reference values.</p>
<h3>Import History</h3>
<p>Import History shows prior uploads and their current status. Open an import to continue mapping or validation, review execution results, or roll back an eligible completed import.</p>
<h3>Permissions</h3>
<ul>
<li><strong>Access Data Import</strong> allows a user to view import history and import details.</li>
<li><strong>Manage Data Import</strong> allows uploads, mappings, validation, review decisions, value translations, and execution.</li>
<li><strong>Rollback Data Import</strong> allows eligible completed imports to be reversed.</li>
</ul>
HTML,
            ],

            'admin.data-imports.create' => [
                'title' => 'Upload Excel Workbook',
                'content_html' => <<<'HTML'
<p>Upload an <strong>.xlsx</strong> workbook for inspection. Uploading a workbook does not create or update staffing records.</p>
<h3>Workbook Requirements</h3>
<ul>
<li>Maximum upload size is 20 MB.</li>
<li>Row 1 of the selected worksheet must contain the column headers.</li>
<li>Workbooks are stored in Insight's protected private storage.</li>
<li>Insight rejects malformed or unsafe workbook XML and applies worksheet, row, and column processing limits.</li>
</ul>
<h3>Next Step</h3>
<p>After the workbook is inspected, select the worksheet you want to use and continue to Column Mapping.</p>
HTML,
            ],

            'admin.data-imports.show' => [
                'title' => 'Data Import Details',
                'content_html' => <<<'HTML'
<p>This page guides an Excel workbook through worksheet selection, field mapping, validation, review, execution, and rollback.</p>
<h3>Column Mapping</h3>
<p>Map every Excel header to an Insight field or choose <strong>Do Not Import</strong>. Candidate Workflow destinations are generated from the selected workflow, including only the properties enabled for each active step. Active Position and Person custom fields are also available.</p>
<h3>Validation and Review</h3>
<p>Run validation before importing. Insight matches People by Person Code first and email second; names alone are not used for automatic matching. Existing People, Positions, and Candidates require an explicit review decision. Unknown lookup values and invalid data are flagged rather than created automatically.</p>
<h3>Value Mappings</h3>
<p>When a spreadsheet uses a different value for a controlled Insight field, use the available value-mapping control to translate it to a current valid value, then revalidate.</p>
<h3>Run Import</h3>
<p>The Run Import action is enabled only when validation has no unresolved errors or review items. Each ready spreadsheet row runs in its own database transaction, and successful changes are written to the encrypted rollback journal.</p>
<h3>Rollback</h3>
<p>Eligible completed imports can be rolled back by a user with Rollback Data Import permission. Insight reverses journaled changes in reverse order. If a record was changed after the import, Insight preserves the newer edit and reports a rollback conflict instead of overwriting it.</p>
HTML,
            ],

            'portal.jobtitlerequirements.index' => [
                'title' => 'Job Title Requirements',
                'content_html' => <<<'HTML'
<p>Job Title Requirements is the central workspace for maintaining the standard skills and tasks associated with IRAD job titles.</p>
<h3>Select a Job Title</h3>
<p>Each card summarizes a job title and its current skill and task counts. Select <strong>Manage Requirements</strong> to open that job title and maintain its requirements.</p>
<h3>Required and Desired Skills</h3>
<p>Required Skills identify capabilities normally expected for the job title. Desired Skills identify useful capabilities that strengthen a candidate but are not mandatory.</p>
<h3>Tasks</h3>
<p>Tasks describe the standard duties or responsibilities commonly associated with the job title.</p>
<h3>Keep Defaults Current</h3>
<p>These requirements provide a consistent starting point for positions that use the job title. Position-specific requirements can still be added when a particular position needs something different.</p>
HTML,
            ],

        ];

        foreach ($pages as $helpKey => $page) {
            PageHelp::updateOrCreate(
                ['help_key' => $helpKey],
                [
                    'title' => $page['title'],
                    'content_html' => $page['content_html'],
                    'is_active' => true,
                ]
            );
        }
    }
}
