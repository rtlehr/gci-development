<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use App\Models\JobTitleSkill;
use App\Models\JobTitleTask;
use Illuminate\Database\Seeder;

class JobTitleRequirementSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->requirements() as $titleName => $requirements) {
            $jobTitle = JobTitle::query()->where('name', $titleName)->first();

            if (! $jobTitle) {
                $this->command?->warn("Job title not found; requirements skipped: {$titleName}");
                continue;
            }

            foreach (['required', 'desired'] as $type) {
                foreach ($requirements[$type] ?? [] as $index => $skill) {
                    JobTitleSkill::updateOrCreate(
                        ['job_title_id' => $jobTitle->id, 'name' => $skill],
                        [
                            'description' => $skill.' knowledge and demonstrated experience.',
                            'requirement_type' => $type,
                            'is_active' => true,
                            'sort_order' => $index + 1,
                        ],
                    );
                }
            }

            foreach ($requirements['tasks'] ?? [] as $index => $task) {
                JobTitleTask::updateOrCreate(
                    ['job_title_id' => $jobTitle->id, 'name' => $task],
                    [
                        'description' => $task.'.',
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ],
                );
            }
        }
    }

    private function requirements(): array
    {
        return [
            'Frontend Developer' => [
                'required' => ['Vue.js', 'TypeScript', 'HTML and CSS', 'Git', 'Responsive Web Design'],
                'desired' => ['Laravel', 'Tailwind CSS', 'Accessibility Testing', 'Automated Frontend Testing'],
                'tasks' => ['Build and maintain frontend features', 'Integrate Vue pages with backend data', 'Resolve user-interface defects', 'Review components for accessibility', 'Maintain frontend documentation'],
            ],
            'Backend Developer' => [
                'required' => ['PHP', 'Laravel', 'REST API Development', 'SQL', 'Git'],
                'desired' => ['Queue Processing', 'Automated Testing', 'Docker', 'Enterprise Integrations'],
                'tasks' => ['Develop backend services and APIs', 'Implement business rules and validation', 'Optimize database queries', 'Write automated tests', 'Support application integrations'],
            ],
            'Full Stack Developer' => [
                'required' => ['Laravel', 'Vue.js', 'TypeScript', 'SQL', 'Git'],
                'desired' => ['DevOps Practices', 'Automated Testing', 'Accessibility', 'Cloud Deployment'],
                'tasks' => ['Deliver end-to-end application features', 'Design reusable components and services', 'Troubleshoot frontend and backend defects', 'Review code changes', 'Maintain technical documentation'],
            ],
            'Software Engineer' => [
                'required' => ['Software Design', 'Object-Oriented Programming', 'Version Control', 'Testing and Debugging', 'Technical Documentation'],
                'desired' => ['Design Patterns', 'Secure Coding', 'CI/CD', 'Cloud Platforms'],
                'tasks' => ['Design software solutions', 'Develop and test application features', 'Participate in code reviews', 'Analyze and resolve defects', 'Document technical decisions'],
            ],
            'DevOps Engineer' => [
                'required' => ['CI/CD', 'Infrastructure Automation', 'Git', 'Linux Administration', 'Scripting'],
                'desired' => ['Docker', 'Kubernetes', 'Azure DevOps', 'Terraform'],
                'tasks' => ['Maintain build and deployment pipelines', 'Automate environment provisioning', 'Monitor deployment health', 'Manage release configurations', 'Troubleshoot pipeline failures'],
            ],
            'Cloud Engineer' => [
                'required' => ['Cloud Architecture', 'Identity and Access Management', 'Networking', 'Infrastructure as Code', 'Cloud Security'],
                'desired' => ['Microsoft Azure', 'Terraform', 'PowerShell', 'Cloud Cost Management'],
                'tasks' => ['Design cloud environments', 'Deploy and maintain cloud resources', 'Implement access controls', 'Monitor platform health and cost', 'Document cloud architecture'],
            ],
            'Systems Administrator' => [
                'required' => ['Windows Server', 'Active Directory', 'Patch Management', 'PowerShell', 'System Monitoring'],
                'desired' => ['VMware', 'Microsoft Azure', 'Backup Administration', 'Group Policy'],
                'tasks' => ['Maintain server availability', 'Manage user and service accounts', 'Apply patches and configuration changes', 'Monitor system health', 'Resolve infrastructure incidents'],
            ],
            'Network Engineer' => [
                'required' => ['TCP/IP Networking', 'Routing and Switching', 'Network Security', 'Troubleshooting', 'Network Documentation'],
                'desired' => ['Cisco Technologies', 'Firewalls', 'VPN Technologies', 'Cloud Networking'],
                'tasks' => ['Configure and maintain network devices', 'Monitor network performance', 'Resolve connectivity incidents', 'Implement network security controls', 'Maintain network diagrams'],
            ],
            'Cybersecurity Analyst' => [
                'required' => ['Security Monitoring', 'Incident Response', 'Vulnerability Management', 'Risk Assessment', 'Security Documentation'],
                'desired' => ['Security+', 'SIEM Tools', 'NIST Frameworks', 'Cloud Security'],
                'tasks' => ['Monitor security events', 'Investigate suspected incidents', 'Track vulnerabilities and remediation', 'Perform security assessments', 'Prepare security reports'],
            ],
            'Database Administrator' => [
                'required' => ['SQL', 'Database Administration', 'Backup and Recovery', 'Performance Tuning', 'Database Security'],
                'desired' => ['MySQL', 'SQL Server', 'High Availability', 'PowerShell'],
                'tasks' => ['Administer database platforms', 'Monitor and tune database performance', 'Manage backups and recovery testing', 'Control database access', 'Support schema and release changes'],
            ],
            'Data Analyst' => [
                'required' => ['Data Analysis', 'SQL', 'Data Visualization', 'Excel', 'Requirements Analysis'],
                'desired' => ['Power BI', 'Python', 'Statistics', 'Data Modeling'],
                'tasks' => ['Analyze operational data', 'Develop reports and dashboards', 'Validate data quality', 'Translate questions into metrics', 'Present findings to stakeholders'],
            ],
            'Business Analyst' => [
                'required' => ['Requirements Elicitation', 'Process Mapping', 'Stakeholder Communication', 'Business Writing', 'Gap Analysis'],
                'desired' => ['Agile Methods', 'User Story Development', 'Data Analysis', 'Facilitation'],
                'tasks' => ['Gather and document requirements', 'Map current and future processes', 'Facilitate stakeholder sessions', 'Support acceptance testing', 'Maintain requirements traceability'],
            ],
            'Quality Assurance Analyst' => [
                'required' => ['Test Planning', 'Manual Testing', 'Defect Management', 'Requirements Analysis', 'Test Documentation'],
                'desired' => ['Automated Testing', 'API Testing', 'Accessibility Testing', 'SQL'],
                'tasks' => ['Develop test plans and cases', 'Execute functional and regression tests', 'Document and track defects', 'Validate acceptance criteria', 'Prepare test status reports'],
            ],
            'UX/UI Designer' => [
                'required' => ['User Experience Design', 'Interface Design', 'Wireframing', 'Accessibility', 'Design Systems'],
                'desired' => ['Figma', 'User Research', 'Prototyping', 'Frontend Development'],
                'tasks' => ['Create wireframes and prototypes', 'Design accessible interfaces', 'Maintain design-system standards', 'Conduct usability reviews', 'Collaborate with product and engineering teams'],
            ],
            'Technical Writer' => [
                'required' => ['Technical Writing', 'Information Organization', 'Editing', 'Audience Analysis', 'Document Management'],
                'desired' => ['Markdown', 'Content Management Systems', 'API Documentation', 'Visual Communication'],
                'tasks' => ['Create user and technical documentation', 'Maintain document standards', 'Interview subject-matter experts', 'Review content for clarity and accuracy', 'Manage document revisions'],
            ],
            'Program Manager' => [
                'required' => ['Program Management', 'Leadership', 'Risk Management', 'Budget Oversight', 'Stakeholder Communication'],
                'desired' => ['PMP Certification', 'Government Contracting', 'Agile Delivery', 'Executive Reporting'],
                'tasks' => ['Direct program planning and execution', 'Manage program risks and dependencies', 'Oversee budgets and schedules', 'Coordinate executive stakeholders', 'Prepare program performance reports'],
            ],
            'Project Manager' => [
                'required' => ['Project Planning', 'Schedule Management', 'Risk Management', 'Stakeholder Communication', 'Status Reporting'],
                'desired' => ['PMP Certification', 'Microsoft Project', 'Agile Methods', 'Budget Tracking'],
                'tasks' => ['Develop and maintain project plans', 'Track milestones and action items', 'Manage project risks and issues', 'Coordinate project meetings', 'Report project status'],
            ],
            'Product Manager' => [
                'required' => ['Product Strategy', 'Roadmap Planning', 'Requirements Prioritization', 'Stakeholder Management', 'Outcome Measurement'],
                'desired' => ['User Research', 'Agile Product Management', 'Data Analysis', 'Market Analysis'],
                'tasks' => ['Define product goals and roadmap', 'Prioritize product requirements', 'Coordinate stakeholder decisions', 'Measure product outcomes', 'Support release planning'],
            ],
            'Scrum Master' => [
                'required' => ['Scrum', 'Facilitation', 'Impediment Removal', 'Team Coaching', 'Agile Metrics'],
                'desired' => ['Certified ScrumMaster', 'Kanban', 'Jira', 'Release Planning'],
                'tasks' => ['Facilitate agile ceremonies', 'Remove delivery impediments', 'Coach team members on agile practices', 'Track sprint performance', 'Support continuous improvement'],
            ],
            'Configuration Manager' => [
                'required' => ['Configuration Management', 'Change Control', 'Release Management', 'Baseline Management', 'Technical Documentation'],
                'desired' => ['ITIL', 'Git', 'Asset Management', 'Automation'],
                'tasks' => ['Maintain configuration baselines', 'Coordinate change-control activities', 'Manage release records', 'Audit configuration items', 'Publish configuration status reports'],
            ],
            'Help Desk Specialist' => [
                'required' => ['Technical Support', 'Incident Management', 'Customer Service', 'Windows Support', 'Troubleshooting'],
                'desired' => ['Active Directory', 'ITIL', 'Remote Support Tools', 'Ticketing Systems'],
                'tasks' => ['Respond to user support requests', 'Diagnose and resolve incidents', 'Document resolutions in the ticketing system', 'Escalate complex issues', 'Maintain user-facing knowledge articles'],
            ],
            'Training Specialist' => [
                'required' => ['Instructional Design', 'Training Delivery', 'Curriculum Development', 'Presentation Skills', 'Learning Assessment'],
                'desired' => ['Learning Management Systems', 'Video Production', 'Technical Writing', 'Virtual Training'],
                'tasks' => ['Develop training plans and materials', 'Deliver instructor-led and virtual training', 'Evaluate learner performance', 'Maintain course content', 'Coordinate training schedules'],
            ],
        ];
    }
}
