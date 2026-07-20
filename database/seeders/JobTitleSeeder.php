<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            ['Frontend Developer', 'Builds and maintains accessible, responsive application interfaces.', 10],
            ['Backend Developer', 'Develops server-side services, APIs, business logic, and integrations.', 20],
            ['Full Stack Developer', 'Develops both frontend and backend application capabilities.', 30],
            ['Software Engineer', 'Designs, develops, tests, and maintains enterprise software solutions.', 40],
            ['DevOps Engineer', 'Automates build, deployment, infrastructure, and operational workflows.', 50],
            ['Cloud Engineer', 'Designs and supports secure cloud infrastructure and platform services.', 60],
            ['Systems Administrator', 'Maintains servers, identity services, patching, and system availability.', 70],
            ['Network Engineer', 'Designs, configures, secures, and supports enterprise networks.', 80],
            ['Cybersecurity Analyst', 'Monitors, assesses, and improves the security posture of systems and data.', 90],
            ['Database Administrator', 'Administers, secures, tunes, and protects enterprise databases.', 100],
            ['Data Analyst', 'Transforms operational data into reports, dashboards, and actionable insights.', 110],
            ['Business Analyst', 'Elicits requirements, documents processes, and bridges business and technical teams.', 120],
            ['Quality Assurance Analyst', 'Plans and executes testing to verify application quality and requirements.', 130],
            ['UX/UI Designer', 'Designs usable, accessible, and consistent digital experiences.', 140],
            ['Technical Writer', 'Creates and maintains clear technical and user documentation.', 150],
            ['Program Manager', 'Leads complex programs, governance, schedules, risks, and stakeholder coordination.', 160],
            ['Project Manager', 'Plans and manages project scope, schedule, resources, risks, and delivery.', 170],
            ['Product Manager', 'Defines product direction, priorities, outcomes, and stakeholder alignment.', 180],
            ['Scrum Master', 'Facilitates agile delivery, removes impediments, and supports team improvement.', 190],
            ['Configuration Manager', 'Controls baselines, changes, releases, and configuration records.', 200],
            ['Help Desk Specialist', 'Provides end-user technical support and incident resolution.', 210],
            ['Training Specialist', 'Develops and delivers training materials, courses, and user enablement.', 220],
        ];

        foreach ($titles as [$name, $description, $sortOrder]) {
            JobTitle::updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true, 'sort_order' => $sortOrder],
            );
        }
    }
}
