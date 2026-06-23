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
			// Custom μ-mark favicon (deep navy field, hot phosphor stroke).
			// The ?v= cache-buster forces browsers that cached the prior
			// Starlight default sparkle to re-fetch the new icon — without
			// it, the 7-day max-age sticks visitors on the old icon.
			favicon: '/favicon.svg?v=2',
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
					label: 'Beta calibration',
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
						{ label: 'Assembly + review-ledger', slug: 'architecture/assembly' },
						{ label: 'Deliverable fidelity', slug: 'architecture/deliverable-fidelity' },
						{ label: 'Sandbox + tool execution', slug: 'architecture/sandbox' },
						{ label: 'API key pool', slug: 'architecture/key-pool' },
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
						{ label: 'ACP — drive from an editor', slug: 'operations/acp' },
						{ label: 'Multi-user host hardening', slug: 'operations/multi-user-hardening' },
						{ label: 'Vault backup + restore', slug: 'operations/vault-backup' },
						{ label: 'CI integration', slug: 'operations/ci-integration' },
					],
				},
				{ label: 'Methodology', slug: 'methodology' },
				{ label: 'Roadmap', slug: 'roadmap' },
				{ label: 'Troubleshooting', slug: 'troubleshooting' },
				{
					label: 'Releases',
					collapsed: true,
					items: [
						{ label: 'v0.9.6 — reliability: the team always finishes the job', slug: 'v0-9-6' },
						{ label: 'v0.9.5 — subscription seats (Clay + GPT-5.5) + per-seat fallbacks', slug: 'v0-9-5' },
						{ label: 'v0.9.4 — the two-lane Leader + autonomy modes', slug: 'v0-9-4' },
						{ label: 'v0.9.3 — Feng-Tui terminal reskin', slug: 'v0-9-3' },
						{ label: 'v0.9.1 — agent role refinement', slug: 'v0-9-1' },
						{ label: 'v0.9.0 — stability + reporting', slug: 'v0-9-0' },
						{ label: 'v0.8.9 — security hardening', slug: 'v0-8-9' },
						{ label: 'v0.8.8 — deterministic assembly validation + codify-the-win', slug: 'v0-8-8' },
						{ label: 'v0.8.6 — self-remediation + JT generativity', slug: 'v0-8-6' },
						{ label: 'v0.8.4 — deliverable fidelity', slug: 'v0-8-4' },
						{ label: 'v0.8.2 — media-assembly + metered-tool tier', slug: 'v0-8-2' },
						{ label: 'v0.8.1 — familial assemblers + review-ledger', slug: 'v0-8-1' },
						{ label: 'v0.8.0 — ACP server', slug: 'v0-8-0' },
						{ label: 'v0.7.2 — conversation-first', slug: 'v0-7-2' },
						{ label: 'v0.7.1 — key pool + Mod Squad', slug: 'v0-7-1' },
						{ label: 'v0.7.0 — key pool + Configuration tab', slug: 'v0-7-0' },
						{ label: 'v0.6.0 — role-language migration', slug: 'v0-6-0' },
						{ label: 'v0.5.0 — Job Templates', slug: 'v0-5-0' },
						{ label: 'v0.4.0 — skill self-codification', slug: 'v0-4-0' },
						{ label: 'v0.3.0 — skill-library keystone', slug: 'v0-3-0' },
						{ label: 'v0.2.2 — web search', slug: 'v0-2-2' },
						{ label: 'v0.2.1 — in-place editing', slug: 'v0-2-1' },
						{ label: 'v0.2.0 — QC-thesis arc', slug: 'v0-2-0' },
						{ label: 'Beta calibration (current ceilings)', slug: 'v0-1-0-beta' },
					],
				},
			],
		}),
	],
});
