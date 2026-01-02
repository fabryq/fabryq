Fabryq

Fabryq provides an opinionated, deterministic path to build Symfony backends.

It combines:
	•	a runtime (the standard bootstrapping and conventions),
	•	a CLI (generators + verification gates), and
	•	a set of templates / rules that keep projects on one consistent track.

Fabryq is built for teams (or solo developers) who want speed without drift: fewer architectural choices, fewer edge cases, and repeatable outcomes.

⸻

What Fabryq is for

Symfony is flexible. Over time, that flexibility often turns into:
	•	inconsistent folder structures,
	•	diverging patterns between teams/projects,
	•	cross-module coupling,
	•	and steadily increasing maintenance cost.

Fabryq reduces that drift by enforcing a single standard path through:
	•	structure (apps/components),
	•	ownership rules (no silent coupling), and
	•	automated gates (verify/review).

⸻

Core principles
	•	One Way Only
	•	There is exactly one standard path for project layout, app/component boundaries, naming, and conventions.
	•	Removable Apps
	•	Every app is self-contained; removing one must not break another.
	•	No Silent Coupling
	•	Data ownership is per app; cross-app ORM relations are prohibited.
	•	Core stays clean
	•	Infrastructure lives in Core; business logic lives in apps.

⸻

Repository layout

This repository is a monorepo.
	•	src/ — core runtime + shared infrastructure
	•	packages/ — standalone packages (runtime/cli/etc.)
	•	skeleton/ — project scaffold templates
	•	examples/ — demos / reference usage
	•	tests/ — automated checks
	•	doc/ — local documentation entry point

⸻

Status

Pre-release / in active development.
	•	APIs and conventions may change.
	•	The CLI gates are the source of truth for what is currently supported.

If you want stability guarantees, wait for the first tagged release.

⸻

Requirements
	•	PHP >= 8.4

(Additional requirements depend on which packages you use and which Symfony version your project targets.)

⸻

Installation

Monorepo development (this repo)
	1.	Install dependencies:
	•	composer install
	2.	Run checks:
	•	composer test
	•	vendor/bin/fabryq verify
	•	vendor/bin/fabryq review

Standalone package usage

If you only want a specific package, install it directly from packages/* and run composer install within that package.

⸻

CLI usage

Typical workflow:
	•	Verify the project against Fabryq rules:
	•	vendor/bin/fabryq verify
	•	Generate a review/report (human-readable):
	•	vendor/bin/fabryq review
	•	Create an app:
	•	vendor/bin/fabryq app:create billing --mount=/billing
	•	Create a component inside an app:
	•	vendor/bin/fabryq component:create billing Checkout

Note: The CLI is intended to be the primary interface. If you can do something “by hand”, the question is usually: should you?

⸻

Documentation

Start here:
	•	doc/README.md

Recommended next steps:
	•	Read the “One Way” and app/component rules.
	•	Run fabryq review on an existing Symfony project (even if it fails) to see what Fabryq expects.

⸻

Roadmap

A useful roadmap is concrete and testable. The project should only claim features that have:
	•	a generator path, and
	•	a gate that enforces the result.

If you maintain a separate roadmap document, link it here.

⸻

Contributing

This project is currently maintained with a strong opinionated direction.
	•	For bug reports: open an issue.
	•	For changes: contact the maintainers first so efforts align with the “One Way” constraints.

⸻

License

Proprietary. See package metadata for details.
