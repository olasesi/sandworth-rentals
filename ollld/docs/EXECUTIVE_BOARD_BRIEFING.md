# Sandworth Homes — Executive & Board Briefing
## "Has this application helped us?" — A plain-English case for the platform

**Audience:** Organization leadership and board members
**Purpose:** Explain what the platform does, how it's changing the business, and the measurable value it delivers.

---

## 1. One-paragraph summary

Sandworth Homes is our **digital front door and back office in one**. It lets customers find a home, book a viewing, apply to rent, pay, and message us — by themselves, online, without a single phone call or paper form. Behind the scenes, the same system automates approvals, tracks every payment, records every tour, and gives the owner a live dashboard of revenue and conversion. It doesn't just list properties; it **sells and manages them end-to-end**.

---

## 2. What the customer can now do themselves (self-service)

Before this platform, much of the customer journey had to be handled by staff manually. Now the customer drives it:

| Capability | What it replaces |
|---|---|
| Browse rent / buy / commercial listings | Printed brochures, cold calls |
| Save (shortlist) properties | Scribbled notes, repeated calls |
| **Book a site tour online** | Phone booking, diary juggling |
| **Apply to rent online** | Paper application forms |
| **Auto-approval for qualifying income** | Manual screening calls |
| **Pay move-in fees & rent online** | Bank visits, cash handovers, chasing |
| **Make an offer to buy** | Phone/email back-and-forth |
| **Message the leasing desk in-app** | Untracked email threads |
| **Full customer history on return** | Flipping through old files |

The customer's whole relationship — every tour, application, payment, offer and message — is **recorded against their account**, so nothing is lost when they come back.

---

## 3. What it means for the organization

### a) Revenue is collected faster and more reliably
- Move-in bundles (first rent + service charge + deposit) are paid **online at the click of a button**.
- Ongoing rent payments flow through the same system into a per-tenant **ledger**.
- In our demonstration run, two tenants generated **₦10,278 in tracked revenue** across 4 online payments — with zero manual chasing.

### b) The sales team spends less time on paperwork
- **Auto-approval** instantly green-lights applicants whose income comfortably covers the rent (about 1.5× annual rent). No screening call needed.
- Staff only review the applicants who actually need a human decision. Time is shifted from **admin** to **selling and customer care**.

### c) We can measure the funnel (not guess)
The owner dashboard tracks the numbers that matter:

| Metric | What it tells us |
|---|---|
| Applications by stage | Where customers stall |
| Auto-approved vs manual | How much we've automated |
| **Tours booked / completed / no-shows** | Real interest + walkthrough leakage |
| **Offers accepted** | Buying conversion |
| Revenue collected (total & this month) | Cash actually landing |
| Average days to lease | Speed of turnaround |

A tour that's **completed** and becomes a tenant, versus a **no-show**, is now visible — so we can follow up precisely where we'd previously lose leads silently.

### d) Better customer experience & retention
- Returning customers get a **History** page showing everything they've gotten from us — instant context for both them and our team.
- Every conversation is in one inbox, not scattered across email and phone.
- Everything is **branded and consistent** — a professional digital presence at all hours.

---

## 4. The cost side

- **No extra staff** required — the platform scales the load.
- Runs on standard, low-cost infrastructure (our existing web server + MySQL database).
- The demo lives on `http://localhost:8080/sandworth-zillow/index.php` with an admin console for property management, offers, tours and messages — no third-party SaaS fees for these features.

---

## 5. Risk & governance notes (for the board)

- **Approvals have a clear audit trail:** each approved application records *who* approved it and *when* (`Auto-approved (qualifying income)` or the staff member's name) — defensible and transparent.
- **Every payment is recorded** with amount, card, reference and timestamp; the ledger gives a full financial history per tenancy.
- **Access is role-based:** customers see only their own data; the **admin** area (properties, offers, tours, messages, approvals, owner report) is restricted to authorized staff.
- **Data is centralized** in one database, making reporting, reconciliation and handover straightforward.

---

## 6. Board-ready recommendation

**Verdict: Yes — the application is helping.** It converts interest into bookings, applications into approvals, and approvals into *paid* tenancies — all measurable. It removes manual bottlenecks, accelerates cash collection, and gives leadership visibility the business never had.

**Suggested next steps:**
1. **Live pilot** — onboard a small pool of real listings and tenants.
2. **Define KPIs** from the owner dashboard (e.g. tours→lease %, days-to-lease, online-payment adoption) and review monthly.
3. **Promote self-service** to reduce staff workload and response time.
4. **Security hardening** before wider deployment (live card processing, backups, access controls) — this demo uses test payments.

---

*Prepared from the live application's owner report and recorded transactions. All figures shown are from demonstration data used to exercise the platform end-to-end.*
