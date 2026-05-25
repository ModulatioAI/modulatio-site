// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
	site: 'https://modulatio.ai',
	integrations: [
		starlight({
			title: 'Modulatio',
			description:
				'Multi-model agent framework. Modulate across models, agents, and registers in one coherent line.',
			customCss: ['./src/styles/modulatio.css'],
			components: {
				// One theme only — suppress the light/dark toggle entirely.
				ThemeSelect: './src/components/ThemeSelect.astro',
			},
			social: [
				{
					icon: 'github',
					label: 'GitHub',
					href: 'https://github.com/ModulatioAI/modulatio',
				},
			],
			sidebar: [
				{
					label: 'Overview',
					slug: 'overview',
				},
				{
					label: 'v0.1.0 Beta calibration',
					slug: 'v0-1-0-beta',
				},
				{
					label: 'Getting Started',
					items: [
						{ label: 'Install', slug: 'getting-started/install' },
						{ label: 'Quickstart', slug: 'getting-started/quickstart' },
						{ label: 'Setup wizard', slug: 'getting-started/wizard' },
						{ label: 'Example: multi-piece deliverable', slug: 'getting-started/example-multi-piece' },
						{ label: 'Example: production-scale Phase 1', slug: 'getting-started/example-production-scale' },
					],
				},
				{
					label: 'Concepts',
					items: [
						{ label: 'Concepts overview', slug: 'concepts/concepts' },
						{ label: 'Agents', slug: 'concepts/agents' },
						{ label: 'Plan lifecycle', slug: 'concepts/plan-lifecycle' },
						{ label: 'Providers & models', slug: 'concepts/providers' },
					],
				},
				{
					label: 'Architecture',
					items: [
						{ label: 'Working memory (5 layers)', slug: 'architecture/working-memory' },
						{ label: 'Skill system', slug: 'architecture/skill-system' },
						{ label: 'Sandbox + tool execution', slug: 'architecture/sandbox' },
						{ label: 'Audit trails', slug: 'architecture/audit-trails' },
					],
				},
				{
					label: 'Reference',
					items: [
						{ label: 'CLI', slug: 'reference/cli' },
						{ label: 'Skill catalog', slug: 'reference/skills' },
						{ label: 'Tool catalog', slug: 'reference/tools' },
						{ label: 'Python API', slug: 'reference/api' },
					],
				},
				{
					label: 'Operations',
					items: [
						{ label: 'Daemon operator guide', slug: 'operations/daemon' },
						{ label: 'Multi-user host hardening', slug: 'operations/multi-user-hardening' },
						{ label: 'Vault backup + restore', slug: 'operations/vault-backup' },
						{ label: 'CI integration', slug: 'operations/ci-integration' },
					],
				},
				{ label: 'Roadmap', slug: 'roadmap' },
				{ label: 'Troubleshooting', slug: 'troubleshooting' },
			],
		}),
	],
});
