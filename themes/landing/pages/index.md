---
title: "Home"
description: "A one-page product site. Every section on this page is data in this file's frontmatter."
layout: landing
TOC: false
hero:
  kicker: "For the people it is for"
  title: "Say what it does in one line."
  lede: "Two sentences on who it is for and what changes once they have it. Keep the claim small enough to be true."
  primary: {label: "Get started", href: "/pricing.html"}
  secondary: {label: "See how it works", href: "/#features"}
  note: "Free to try. No account needed."
  screen:
    alt: "The product's front screen, showing today's headline figure and what comes next"
    title: "Your product"
    subtitle: "Today"
    value: "42"
    label: "the figure your users check first"
    status: "up 3 since yesterday"
    trend: up
    series: [12, 18, 15, 22, 27, 25, 31, 36, 33, 39, 42]
    marks:
      - {at: 0.4, label: "Launch"}
      - {at: 1.0, label: "Now"}
    rows:
      - {label: "Next", value: "Tomorrow, 09:00"}
      - {label: "Then", value: "Friday, 14:30"}
stats:
  - {value: "12k", label: "people using it"}
  - {value: "99.9%", label: "uptime last year"}
  - {value: "0", label: "trackers"}
  - {value: "4 min", label: "to set up"}
featuresTitle: "What it does"
features:
  - title: "Lead with the outcome"
    body: "Say what the person gets, not what the software has. One idea per section, and a readout beside it that shows the idea instead of an icon."
    readout: {kind: value, value: "2 h 10 m", label: "saved this week", note: "Measured on the device, not estimated."}
  - title: "Show the state, not a screenshot"
    body: "A list readout takes labels and values from this file, so the page can show the product's real shape before there is a product to photograph."
    readout:
      kind: list
      items:
        - {label: "Inbox", value: "0"}
        - {label: "Waiting on you", value: "3"}
        - {label: "Done today", value: "17"}
  - title: "Works where they are"
    body: "Three short lines beat a paragraph when the feature is a promise. Say what is stored, what is sent, and what is not."
    readout: {kind: text, lines: ["Stored on the device", "Syncs when it can", "Sends nothing you did not choose"]}
  - title: "Fits on a wrist"
    body: "A dial readout holds one number, a unit and a direction. Use it for the figure that matters when hands are busy."
    readout: {kind: dial, value: "42", unit: "", label: "rising", trend: up}
plansTitle: "Plans"
plans:
  - name: "Free"
    price: "£0"
    summary: "For one person, getting started."
    href: "/pricing.html"
    action: "Download"
  - name: "Pro"
    price: "£9"
    period: "a month"
    summary: "Everything, for one person."
    href: "/pricing.html"
    action: "Start Pro"
    featured: true
  - name: "Team"
    price: "£29"
    period: "a month, five people"
    summary: "Shared with the people you work with."
    href: "/pricing.html"
    action: "Set up a team"
planRows:
  - {label: "Projects", values: ["One", "Unlimited", "Unlimited"]}
  - {label: "People", values: ["1", "1", "5"]}
  - {label: "History", values: ["30 days", "Two years", "Two years"]}
  - {label: "Alerts", values: [false, true, true]}
  - {label: "Export", values: [false, true, true]}
  - {label: "Shared views", values: [false, false, true]}
plansNote: "Prices include tax. Change or cancel any time; what you downloaded stays yours."
faqTitle: "Questions"
faq:
  - q: "Is there a free plan?"
    a: "Yes. The free plan is the whole product for one person and one project, with no time limit. Upgrade when you need history, alerts or a second person."
  - q: "Does it work offline?"
    a: "Everything is stored on the device and syncs when there is a connection. Nothing you do waits for the network."
  - q: "Can I export my data?"
    a: "On Pro and Team, to CSV and JSON, at any time. Your data is yours; the export exists so that stays true."
  - q: "Who is behind it?"
    a: "Say who you are in two sentences, and where to write. People buy from people."
closing:
  title: "Ready when you are."
  lede: "Repeat the promise, shorter. Then the one button."
  note: "Free to try. No account needed."
footerLinks:
  - {label: "Pricing", href: "/pricing.html"}
  - {label: "Changelog", href: "/changelog.html"}
  - {label: "Privacy", href: "/privacy.html"}
jsonld:
  "@context": https://schema.org
  "@type": WebSite
  name: Your product
---

## About this starter

Everything above this line is data in the frontmatter of `pages/index.md`:
the hero and its screen, the figures, the features and their readouts, the
plans and the comparison rows, the questions and the closing call. This
paragraph is the page's Markdown body. For a finished site built on the
same template, see the Tidewatch example in
[smgen-extras](https://seanmorris.github.io/smgen-extras/examples/tidewatch/).
