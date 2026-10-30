---
title: "Tidewatch"
description: "Tide times, heights and the next safe window for any harbour, offline, on a watch or a phone."
layout: landing
TOC: false
hero:
  kicker: "For people who work the shore"
  title: "Know the tide before you need it."
  lede: "Tidewatch shows the next high and low water, the height now, and how long the slip stays dry. It works offline, on a watch face or a phone, for four thousand harbours."
  primary: {label: "Get Tidewatch", href: "/pricing.html"}
  secondary: {label: "See what it does", href: "/#features"}
  note: "Free for one harbour. No account needed."
  image: "/hero.svg"
  imageAlt: "A tide chart for one day with both high waters marked"
featuresTitle: "Built for the shore, not the app store"
features:
  - title: "The next window, not a graph"
    body: "The front screen answers one question: how long until the water reaches the mark you care about. The graph is there when you want it, one swipe away."
  - title: "Offline by design"
    body: "Predictions for a whole year load once and stay on the device. No signal at the slipway, no problem at the slipway."
  - title: "Your marks, your words"
    body: "Set a mark at the height that matters, the sill, the step, the mooring, and name it. Tidewatch counts down to it in plain language."
  - title: "On your wrist"
    body: "A watch face with the time, the height, and the arrow that says rising or falling. Nothing else, because your hands are busy."
plansTitle: "Plans"
plans:
  - name: "Free"
    price: "£0"
    summary: "One harbour, this year."
    includes: ["One harbour", "Phone and watch", "A year of predictions"]
    href: "/pricing.html"
    action: "Download"
  - name: "Plus"
    price: "£12"
    period: "a year"
    summary: "Every harbour, with marks and alerts."
    includes: ["Four thousand harbours", "Unlimited marks", "Alerts before a mark", "Ten years of predictions"]
    href: "/pricing.html"
    action: "Start Plus"
    featured: true
  - name: "Crew"
    price: "£40"
    period: "a year, five people"
    summary: "Shared marks for a boat, a club or a yard."
    includes: ["Everything in Plus", "Shared marks and alerts", "Export to the logbook"]
    href: "/pricing.html"
    action: "Set up a crew"
plansNote: "Prices include VAT. Change plans or stop any time; predictions you downloaded stay on the device."
faqTitle: "Questions"
faq:
  - q: "Where do the predictions come from?"
    a: "From the published harmonic constants for each station, computed on the device. Tidewatch does not fetch a daily forecast and does not need to."
  - q: "How accurate is it?"
    a: "As accurate as the station constants, usually within ten minutes and a few centimetres. Weather can move real water a long way from any prediction, so treat a mark as a plan, not a promise."
  - q: "Does it work without a phone?"
    a: "On most watches, yes, once the year's predictions have synced. The watch keeps its own copy."
  - q: "Can I add a harbour that is missing?"
    a: "Send the constants or a reference to a published table and it goes into the next release for everyone."
jsonld:
  "@context": https://schema.org
  "@type": SoftwareApplication
  name: Tidewatch
  applicationCategory: UtilitiesApplication
  operatingSystem: iOS, Android, watchOS
---

## A note on what this site is

Tidewatch is a fictional product. This page demonstrates the SMGen **landing**
theme, one of the base themes in the [smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#landing)
collection. Every section above it comes from the YAML at the top of
`pages/index.md`; this paragraph is the page's Markdown body.
