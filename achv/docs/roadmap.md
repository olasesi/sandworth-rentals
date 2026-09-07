# Sandworth Living Roadmap

This prototype is the first layer of a Zillow-style rental platform in PHP. It captures the product split visible across Zillow's public home page, renter search, and Rental Manager flows.

## What is already in this repo

- Public home page with branded search entry point
- Buyer planning page inspired by Zillow Plan
- Homes-for-sale discovery page
- Rental search page with basic filters
- City rentals market page
- Property detail page
- Landlord Rental Manager dashboard
- Seed data structured for future database storage
- Shared layout, routing, and styling for expansion

## Zillow-style capabilities to build next

### Phase 1: Marketplace foundation

- SEO-friendly listing URLs and city landing pages
- Saved searches, favorites, and recently viewed listings
- Real map integration with clustering and drawn-area search
- Listing CMS for photos, amenities, availability, and publishing controls

### Phase 2: Renter workflow

- Account registration and identity verification
- Tour scheduling with calendar availability
- Inquiry inbox and landlord messaging
- Reusable rental application with document uploads
- Application status hub for renters

### Phase 3: Landlord operations

- Property onboarding and multi-listing management
- Lead pipeline with notes, reminders, and bulk actions
- Application review queue
- Lease template builder and e-signature flow
- Maintenance requests and move-in or move-out tracking

### Phase 4: Financial and compliance layer

- Rent collection and payout tracking
- Utility, deposit, and fee ledger
- Screening integrations for background and credit checks
- Audit trails, role-based access, and admin approvals
- Jurisdiction-aware lease and fair-housing compliance tooling

## Recommended PHP stack for the real build

- PHP 8.2+
- MySQL or MariaDB
- A lightweight MVC structure or Laravel if you want faster enterprise features
- Redis for sessions, queues, and notifications
- JavaScript map/search layer with Mapbox or Google Maps
- Payment integrations such as Stripe
- E-signature integration such as DocuSign, Dropbox Sign, or a local provider

## Important product note

Building "all Zillow functionality" is a large multi-phase product, not a one-screen website. The right path is to match the user journeys that matter most for your company first, then wire the heavy integrations in phases.
