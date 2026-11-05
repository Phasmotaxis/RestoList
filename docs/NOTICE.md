# Notice and Clean-Room Attribution

- **Root Navigation**: [Project Overview](../README.md)
- **License**: [MIT License](LICENSE.md)
- **Architecture Context**: [Architecture Decision Records](dev/specifications/architecture.md)
- **Changelog**: [Release History](CHANGELOG.md)

---

## 1. Clean-Room Implementation Statement
This codebase has been constructed from first principles as a clean-room implementation for academic evaluation in Information Management at Cavite State University (CvSU).
- All CSS stylesheets, layouts, color systems, and typographic hierarchies are authored without external CSS frameworks (such as Bootstrap, Tailwind CSS, or Bulma).
- All PHP routing, middleware pipelines, error handlers, and database abstraction layers are authored without external micro-frameworks (such as Laravel, Slim, or Symfony).
- All visual design conventions adhere strictly to WCAG AA color contrast specifications and clean typographic hierarchy.

---

## 2. Upstream Base Images & Tools
The following open-source container base images and tools are utilized in the development and runtime environment under their respective permissive licenses:

- **PHP 8.3 FPM Alpine**: PHP License v3.01 / MIT / Alpine Linux (GPL-2.0 / MIT / BSD)
- **Nginx 1.25 Alpine**: 2-clause BSD-like license
- **MySQL Community Server 8.0**: GPLv2 with FOSS Exception
- **GNU just**: Apache License 2.0
