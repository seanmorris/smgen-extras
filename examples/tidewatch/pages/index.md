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
  screen:
    alt: "Tidewatch's front screen for Harbour Mouth: three hours forty minutes until the slip is dry, the water at 4.1 metres and rising, today's tide curve with both high waters marked, and the next three tides"
    title: "Harbour Mouth"
    subtitle: "Thu 28 March"
    value: "3 h 40 m"
    label: "until the slip is dry"
    status: "4.1 m now, rising"
    trend: up
    series: [1.2, 2.1, 3.4, 4.4, 4.8, 4.5, 3.6, 2.4, 1.3, 0.7, 0.9, 1.8, 3.0, 4.1, 4.6, 4.3, 3.4, 2.2, 1.2]
    marks:
      - {at: 0.22, label: "HW 05:12"}
      - {at: 0.5, label: "LW 11:26"}
      - {at: 0.78, label: "HW 17:41"}
    rows:
      - {label: "High water", value: "05:12 · 4.8 m"}
      - {label: "Low water", value: "11:26 · 0.7 m"}
      - {label: "High water", value: "17:41 · 4.6 m"}
stats:
  - {value: "4,112", label: "harbours"}
  - {value: "10 yrs", label: "of predictions on the device"}
  - {value: "0 B", label: "sent while you work"}
  - {value: "18 MB", label: "for a whole year"}
featuresTitle: "Built for the shore, not the app store"
features:
  - title: "The next window, not a graph"
    body: "The front screen answers one question: how long until the water reaches the mark you care about. The graph is there when you want it, one swipe away."
    readout: {kind: value, value: "3 h 40 m", label: "until the slip is dry", note: "Then 2 h 05 m until the water is over the sill."}
  - title: "Offline by design"
    body: "Predictions for a whole year load once and stay on the device. No signal at the slipway, no problem at the slipway."
    readout: {kind: text, lines: ["2026 stored for Harbour Mouth and 3 more", "Last sync a month ago, and fine", "Nothing sent while you work"]}
  - title: "Your marks, your words"
    body: "Set a mark at the height that matters, the sill, the step, the mooring, and name it. Tidewatch counts down to it in plain language."
    readout:
      kind: list
      items:
        - {label: "The sill", value: "2.4 m"}
        - {label: "The step", value: "1.1 m"}
        - {label: "The mooring", value: "3.6 m"}
  - title: "On your wrist"
    body: "A watch face with the time, the height, and the arrow that says rising or falling. Nothing else, because your hands are busy."
    readout: {kind: dial, value: "4.1", unit: "m", label: "rising", trend: up}
plansTitle: "Plans"
plans:
  - name: "Free"
    price: "£0"
    summary: "One harbour, this year."
    href: "/pricing.html"
    action: "Download"
  - name: "Plus"
    price: "£12"
    period: "a year"
    summary: "Every harbour, with marks and alerts."
    href: "/pricing.html"
    action: "Start Plus"
    featured: true
  - name: "Crew"
    price: "£40"
    period: "a year, five people"
    summary: "Shared marks for a boat, a club or a yard."
    href: "/pricing.html"
    action: "Set up a crew"
planRows:
  - {label: "Harbours", values: ["One", "4,112", "4,112"]}
  - {label: "People", values: ["1", "1", "5"]}
  - {label: "Predictions", values: ["This year", "Ten years", "Ten years"]}
  - {label: "Marks", values: [false, "Unlimited", "Shared"]}
  - {label: "Alerts before a mark", values: [false, true, true]}
  - {label: "Phone and watch", values: [true, true, true]}
  - {label: "Export to the logbook", values: [false, false, true]}
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
closing:
  title: "Know the tide before you need it."
  lede: "One harbour free, every harbour for twelve pounds a year."
  note: "No account. No forecast fetches. No ads."
footerLinks:
  - {label: "Pricing", href: "/pricing.html"}
  - {label: "Changelog", href: "/changelog.html"}
  - {label: "Privacy", href: "/privacy.html"}
jsonld:
  "@context": https://schema.org
  "@type": SoftwareApplication
  name: Tidewatch
  applicationCategory: UtilitiesApplication
  operatingSystem: iOS, Android, watchOS
---

## A note on what this site is

Tidewatch is a fictional product. This site is the finished example for the
SMGen **landing** theme, one of the base themes in the
[smgen-extras](https://seanmorris.github.io/smgen-extras/themes.html#theme-landing)
collection. Its template and stylesheet are the theme's own, read straight
from `themes/landing`; only the pages here are Tidewatch's.
