# ADR-001 — Foundation: IAM, Multi-Company, Customer

**Status:** Accepted  
**Date:** 2026-11-03  
**Repo:** https://github.com/akhlaghimhd/hamarehCRM.git  

## Context

hamareCRM needs a shared foundation before marketing, orders, and production modules. Requirements locked with product owner:

- UI: Filament
- Users float across companies with different roles per company
- Permissions fixed from backend (seed); roles created by admin from permission pool
- Customers fully registerable independent of opportunities; rich fields from day one
- Login: username + password (SMS later)

## Decision

### Isolation

- Single database, `company_id` on operational tables
- `BelongsToCompany` trait: global scope + auto-set on create
- Current company resolved from session / user default membership
- Policies enforce ability within current company
- Cross-company leak tests required

### Identity

- `users.username` unique for login
- `email` optional for notifications
- Membership: `company_user`
- Roles on membership: `company_user_role` (many roles per company membership)

### RBAC

- `permissions` seeded only (`module.action` codes)
- `roles` created by admin; optional `company_id` (null = template/global for that admin scope)
- Phase-1 seeded role templates: admin, sales, support

### Customer

- `customers.type`: individual | organization
- Full Iranian operational fields from day one
- `customer_contacts` for people at organization (and optional for individual)
- No dependency on opportunities table

### UI

- Filament panel, Persian RTL, mobile-friendly tables/forms
- Buttons gated by permissions

## Consequences

- All later modules (opportunities, orders) must use `BelongsToCompany` and policies
- New permissions require code/seed deploy, not UI invent
- Switching company clears/reloads scoped UI data

## Out of scope

Opportunities, products, invoices, OTP login, customer self-service portal
