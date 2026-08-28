# Health v2 – UI and Interaction Design

## 1. Purpose

Health is a private health and wellbeing journal inside Nextcloud.

The interface should feel:

* calm
* lightweight
* private
* understandable
* non-medical
* non-judgmental

The primary interaction should be recording information in seconds and reviewing it later without requiring interpretation by Health.

Health is a journal, not a dashboard that demands constant attention.

# Ergänzungen für Health v2 – UI.md

## Ergänzung zu Abschnitt 1 – Purpose

Health follows the design principle:

> **Minimalistic and powerful.**

The interface should expose powerful functionality without visual complexity.

Prefer:

* fewer visible controls
* clear hierarchy
* contextual actions
* progressive disclosure
* predictable interactions

over:

* large permanent toolbars
* deeply nested menus
* crowded dashboards
* duplicate actions
* hidden functionality that can only be discovered through complex navigation

A user should be able to understand the primary action of a screen within a few seconds.

---

# Navigation and Action Principle

Health distinguishes between:

1. **View navigation**
2. **Actions inside a view**

The side navigation is primarily responsible for switching between major application views:

```text
Journal
Goals
Statistics
```

Settings remains the dedicated footer action rather than a normal content-navigation item.

Other functionality must not depend on the side navigation being visible.

This is especially important on narrow screens where the Nextcloud navigation may be collapsed.

With the exception of switching between main views, every action available to the user must also be reachable from the active content area.

Examples include:

* Add entry
* Edit entry
* Delete entry
* Quick entry
* Start check-in
* Start check-out
* Change date
* Change statistics period
* Select metric
* Configure goals
* Configure reminders
* Access contextual settings

The side navigation must not become the only access point for actions belonging to the current screen.

---

# Health v3 Journal Experience

For the currently implemented five-metric slice, the following rules supersede older illustrative Today, Quick Entry, Add Entry, and Journal examples in this document.

Health has one primary Journal view. Opening `/apps/health/` replaces the URL with `/apps/health/journal/YYYY-MM-DD` for today's local date; the route date is the canonical selected-day state. Goals, Statistics, and Settings have their own browser routes. Journal deep links and browser back/forward must remain functional after refresh. Invalid or future Journal dates replace to today's Journal route.

The primary navigation contains Journal, Goals, and Statistics. Settings is a dedicated footer item in the Nextcloud application navigation, not a normal content-navigation item. Selecting Journal always opens today's canonical `/journal/YYYY-MM-DD` route, including when the current route is a historical Journal day. There is no separate Today view.

## Statistics v3

Statistics v3 replaces the former Overview screen. There is no Overview navigation item or browser route. Statistics remains a descriptive, historical visualization view: it supports the selected period and one or more enabled chartable metrics, but never provides a health score, diagnosis, recommendation, correlation, or causal interpretation. This correction supersedes the older Overview and single-metric Statistics examples below.

Journal shows its full selected date and an always-visible `< Previous day`, `Today`, `Next day >` control group. On today, Today and Next are disabled; on historical dates Today is primary and Next is enabled only when it reaches no later than today. The Journal entries heading places four right-aligned, icon-only action clusters on the same desktop row: primary `+` Add entry, secondary `🥛` water, secondary `☕️` coffee/tea, and secondary `⏱️` break. The coffee/tea and break controls use direct `NcActionButton` children in `NcActions` with one inline default action and a three-dot overflow menu. There is no large Quick Entry card and no additional Add Entry action beneath the journal.

Add entry opens an official Nextcloud dialog containing only Stress, Energy, and Mood. Each selector uses its centrally configured Journal metric icon. The user can switch between these scale metrics, choose a value from 1 through 5, add an optional single-line note, cancel, or save. Hydration and Break use dedicated immediate actions and do not appear in this dialog.

All five implemented metrics render as collapsed top-level aggregates for a newly loaded Journal date. Stress, Energy, and Mood show an icon, count, and average `NcProgressBar` only when they have at least one entry; the precise average is available only in the accessible text. Empty groups show only title and icon, with no `0×`, average bar, or disclosure arrow. Expanded children are newest-first and show their recorded time, exact accessible scale value, and a three-dot entry menu. Metric titles remain normal foreground text; only the centrally configured metric icons and progress bars use an accent color. Headers with entries toggle the disclosure from their non-action area, while the rightmost arrow is its own equivalent disclosure control and child quick actions never toggle a group.

Hydration and Break remain grouped only in the presentation layer; their database and API entries are atomic. Hydration displays only non-zero water `🥛`, coffee `☕️`, and tea `🫖` aggregate counts, while all concrete drink entries remain in one expanded group. Water includes `small_glass` and `large_glass`; coffee includes `coffee`, `cappuccino`, `espresso`, `double_espresso`, `latte_macchiato`, and `cafe_au_lait`; tea is `tea`. The default Coffee action records `coffee`, while its dropdown offers all coffee variants and Tea. Break shows its count only when non-zero; its default is `short` `⏱️`, with `regular` `⏸️`, `short_walk` `🚶`, `long_walk` `🥾`, `mindfulness` `🧘`, and `fresh_air` `🌬️` available in its menu. Hydration and Break inline quick-add controls remain secondary. Expanded hydration and break entries display their respective symbols before the translated option label.

Editing is inline. Scale entries reuse the scale input and show a progress preview; event entries reuse the appropriate allowed-option control. The editing region has a subtle border/background, explicit Save and Cancel actions, and no warning styling. Delete requires confirmation in an official Nextcloud dialog. Successful create, update, and delete operations refresh the shared journal without a page reload.

Every aggregate's non-interactive header is an accessible disclosure button, so its full row area toggles expansion without nesting interactive controls. Expanded entry menus list Edit and Delete, followed by a separator and non-interactive `ID: <id>` and `Source: <source>` metadata. Source labels are Web, API, Mobile, and Notification.

A full-width daily note is placed before Journal entries and uses `NcRichContenteditable` while preserving the owner-scoped plain-text daily-note API contract. It autosaves 800 ms after the final edit, does not save unchanged content, serializes requests, and carries the original date with every pending save. Creation on a historical date combines that selected local date with the current local clock time and never silently creates an entry for today.

All icon-only actions require translated accessible labels. Menus, dialogs, popovers, disclosure controls, editing, and date navigation must be keyboard operable with logical focus behavior and visible focus indication. The complete interaction set remains available when the Nextcloud side navigation is collapsed and at narrow viewport widths.

---

# Native Nextcloud integrations

Health Settings includes an Integration section after Metrics, Profile, and Units. Its Daily Note search control is off by default and clearly explains that enabling it makes only the current user's Daily Note words available to Nextcloud Unified Search.

The native Health Dashboard widget shows the local current day. It keeps a compact action row for Check-in, Check-out, water, coffee/tea, and breaks (subject to the user's enabled metrics), followed by concise values that have actually been recorded today. It does not show Daily Note content. Loading or saving one action must not blank the widget; the Open Journal link always remains available.

# Menu Size

Menus should remain short and understandable.

A menu should normally contain no more than:

```text
5 items
```

If more than five actions appear necessary, reconsider the information architecture.

Possible solutions include:

* grouping related actions
* moving configuration into a dedicated view
* using contextual controls
* using progressive disclosure
* splitting actions by task
* using a dedicated secondary menu

Avoid large generic overflow menus containing unrelated actions.

---

# Horizontal Context Menus

Health may use compact horizontal contextual menus with icons and labels.

This interaction style is especially suitable for:

* journal entry actions
* metric configuration
* statistics controls
* quick entry
* module actions
* chart actions

Conceptually:

```text
[ ✏ Edit ] [ 🕒 Time ] [ 📝 Note ] [ 🗑 Delete ]
```

or:

```text
[ + Entry ] [ ✓ Check-in ] [ 💧 Water ] [ ☕ Break ]
```

The interaction may take inspiration from compact contextual interfaces such as:

* the column action menus in Nextcloud Tables
* compact command interfaces used by Microsoft OneNote

These references describe interaction density and discoverability, not a requirement to visually copy another application.

Where possible, use official Nextcloud Vue components for the implementation.

---

# Icon Usage in Menus

Icons may support fast recognition.

However:

> An icon must not replace an understandable text label for an action.

Every action button must have a visible or otherwise clearly accessible label.

Preferred:

```text
[ + Add entry ]
```

rather than:

```text
[ + ]
```

Preferred:

```text
[ ✏ Edit ]
```

rather than:

```text
[ ✏ ]
```

For extremely constrained responsive layouts, an icon-only representation may only be used when:

* the accessible name remains present
* the action is unambiguous
* the corresponding tooltip or accessible description exists
* the Nextcloud component supports the pattern appropriately

Icon-only controls must not be the default design.

---

# Button Labels

Every button must have a meaningful action label.

Labels should describe the action rather than the technical mechanism.

Good:

```text
Add entry
Save check-in
Record stress
Previous day
Delete entry
Open settings
```

Avoid:

```text
OK
Submit
Action
Go
Click here
```

where a more specific label is possible.

Accessible names must remain meaningful without surrounding visual context.

For example, several buttons named:

```text
Edit
```

in a list should expose enough accessible context for assistive technology to determine which item is being edited where technically feasible.

---

# Accessibility as a Core Requirement

Accessibility is a first-class product requirement.

Health should implement as much accessibility as reasonably possible from the beginning rather than treating it as a later enhancement.

Implementation should consider:

* keyboard navigation
* screen readers
* semantic HTML
* accessible names
* visible focus states
* logical tab order
* sufficient contrast
* browser zoom
* reduced motion preferences
* responsive layouts
* color-independent status communication
* understandable form labels
* validation messages associated with their fields
* accessible dialogs
* accessible menus
* accessible charts
* touch target size

Prefer native semantics and accessible Nextcloud Vue components over custom interaction implementations.

---

# Keyboard Navigation

All core functionality must be usable without a mouse.

This includes:

* navigation
* quick entry
* metric selection
* check-in
* check-out
* journal actions
* date navigation
* statistics controls
* settings
* dialogs

Custom Health components must preserve logical keyboard interaction.

Do not implement clickable non-semantic elements such as:

```html
<div @click="...">
```

when an appropriate button or interactive Nextcloud component exists.

---

# Focus Management

Opening a dialog, menu or contextual interaction must place focus appropriately.

Closing it should normally return focus to the control that opened it.

After actions such as saving an entry, focus should remain predictable.

Do not unexpectedly move keyboard focus to unrelated parts of the page.

---

# Color and Accessibility

Colors support meaning but never define meaning alone.

Example:

Do not display only:

```text
green circle
yellow circle
orange circle
```

without another indication.

Instead combine:

```text
icon + color + accessible label
```

Example:

```text
✓ Goal reached
◐ Partially reached
! Outside your target
– No data
```

The exact icons are implementation details.

---

# Charts and Accessibility

Charts must not be the only representation of statistical information.

Important values should also be available as text.

Example:

```text
Average stress: 2.8 / 5
Minimum: 1
Maximum: 5
26 entries
```

The chart provides additional visual context.

Users who cannot interpret the visual chart must still be able to access the meaningful statistics.

Where technically practical, chart data should provide an accessible textual or tabular representation.

---

# Responsive Functional Parity

Collapsing the Nextcloud side navigation must not remove functionality from the active view.

Example on desktop:

```text
Side navigation
    Journal
    Goals
    Statistics
    Settings

Content
    Add entry
    Check-in
    Date controls
    Journal actions
```

Example on a narrow screen:

```text
Content
    Add entry
    Check-in
    Date controls
    Journal actions
```

The user may need to open the Nextcloud navigation to switch from:

```text
Journal → Statistics
```

but they must not need to open it merely to:

```text
add an entry
edit an entry
change today's date
record water
start a check-in
```

---

# Contextual Actions

Actions should appear near the content they affect.

Examples:

A journal entry exposes its own entry actions.

A chart exposes chart-related controls.

A goal exposes goal-related actions.

Avoid placing unrelated global actions in the side navigation merely because it provides available space.

---

# Toolbar Density

A screen should not display every possible action permanently.

Prefer a small primary command area containing approximately:

```text
2–5 relevant actions
```

For example Today:

```text
[ Add entry ] [ Check-in ] [ Quick entry ▾ ]
```

rather than eight independent buttons for every enabled module.

Frequently used module actions may be exposed through a compact horizontal Quick Entry control.

---

# Progressive Disclosure

Advanced actions should become visible when they are relevant.

Example:

Journal entry collapsed:

```text
15:30   Stress   4 / 5
```

Selected entry:

```text
15:30   Stress   4 / 5

[ Edit ] [ Change time ] [ Add note ] [ Delete ]
```

Do not show all entry-management actions permanently for every row.

This reduces visual noise while keeping functionality discoverable.

---

# Updated UI Principle

Health follows these priorities:

When choosing between:

```text
more controls
```

and:

```text
clearer focus
```

prefer clearer focus.

When choosing between:

```text
deep menus
```

and:

```text
short contextual actions
```

prefer short contextual actions.

When choosing between:

```text
icon-only action
```

and:

```text
icon + understandable label
```

prefer icon + understandable label.

When choosing between:

```text
desktop-only convenience
```

and:

```text
functional parity on narrow screens
```

prefer functional parity.

When choosing between:

```text
visual elegance
```

and:

```text
accessibility
```

accessibility wins.

Health should be:

> **Minimalistic, powerful, accessible and predictable.**


---

# 2. UI Technology

The frontend uses:

* Vue 3
* TypeScript
* `@nextcloud/vue`
* Chart.js for charts only

General-purpose UI elements must use `@nextcloud/vue` components whenever an appropriate component exists.

Do not introduce another UI framework.

Do not create custom replacements for standard Nextcloud:

* buttons
* inputs
* selects
* dialogs
* menus
* navigation
* checkboxes
* toggles
* notifications
* loading indicators
* empty states

Health-specific Vue components may compose Nextcloud components.

Examples:

```text
MetricInput
QuickEntry
DailyStatus
MetricChart
HealthHeatmap
GoalStatus
CheckinForm
```

Before importing a Nextcloud Vue component, verify its current API against the `@nextcloud/vue` version installed in the project.

---

# 3. Main Navigation

Health has three primary content views:

```text
Journal
Goals
Statistics
```

Settings is a dedicated footer action.

The application uses the standard Nextcloud application layout and navigation patterns.

Conceptually:

```text
┌─────────────────────────────────────────────────────────┐
│ Nextcloud header                                        │
├───────────────────┬─────────────────────────────────────┤
│                   │                                     │
│ Journal           │          Active view                │
│ Goals             │                                     │
│ Statistics        │                                     │
│                   │                                     │
│ Settings          │                                     │
│                   │                                     │
└───────────────────┴─────────────────────────────────────┘
```

The navigation should not contain module-specific entries such as:

```text
Stress
Weight
Sleep
Hydration
```

Modules belong inside the journal experience, not the global application navigation.

The side navigation is exclusively responsible for switching between primary application views. It must not be the sole access point for actions within those views.

---

# 4. Default View

Opening Health loads:

```text
Journal (today)
```

Journal is the primary working screen.

The user should normally be able to record Health information without navigating elsewhere.

---

# 5. Today View

The Today view answers:

> What have I recorded today, and is there anything I want to record now?

Suggested structure:

```text
Today
Sunday, 16 August

[ Morning check-in ]

Quick entry
[ Stress ] [ Water ] [ Break ] [ Energy ] [ + ]

Today's goals
Hydration    ●●●●○○    4 / 6
Breaks       ●●○       2 / 3
Check-in     ✓

Journal
08:20  Energy       4 / 5
08:20  Stress       2 / 5
10:30  Large glass
12:15  Break
15:10  Stress       4 / 5

[ Add entry ]

[ Evening check-out ]
```

Only enabled modules are shown.

---

# 6. Today Screen Priority

Information is ordered by immediate usefulness:

1. due check-in or check-out
2. quick entry
3. today's goals
4. today's journal
5. optional actions

Do not place statistics or long-term charts prominently on Today.

Today is for recording.

Statistics are for reflection.

---

# 7. Quick Entry

Quick Entry is one of the most important interactions in Health.

It should allow common actions with one or very few interactions.

Examples:

```text
💧 Large glass
☕ Coffee
☕ Break
🚶 Short walk
⚡ Energy
◉ Stress
```

The actual icon set must use icons compatible with the Nextcloud frontend environment.

Only modules configured with:

```text
showInQuickEntry = true
```

appear here.

---

# 8. Immediate Entry

Event-based actions should normally save immediately.

Example:

User clicks:

```text
Large glass
```

Health:

1. creates the entry
2. gives subtle confirmation
3. updates today's status
4. keeps the user on Today

Do not open a dialog when no additional information is necessary.

---

# 9. Value Entry

Metrics requiring a value open a small focused interaction.

Example Stress:

```text
How stressed do you feel?

1     2     3     4     5
○     ○     ●     ○     ○

[ Save ]
```

The interaction should require as little typing as possible.

Prefer:

* buttons
* scales
* predefined options
* appropriate native/Nextcloud controls

over free-text input.

---

# 10. Add Entry

The global action:

```text
Add entry
```

opens a selection containing only enabled modules.

Example:

```text
Add entry

Stress
Energy
Mood
Hydration
Break
Movement
Sleep
Weight
```

Disabled modules are not shown.

The user should never be asked for data belonging to a module they did not enable.

---

# 11. Module Entry Flow

Selecting a module opens its appropriate input.

Examples:

## Stress

```text
1 – 5 scale
```

## Hydration

```text
Small glass
Large glass
Coffee
Other
```

## Sleep

One visual form:

```text
Sleep

Duration
[ 7 h ] [ 15 min ]

How rested do you feel?
1  2  3  4  5

[ Save ]
```

Internally this creates:

```text
sleep_duration
sleep_recovery
```

as separate entries.

The technical metric structure must not unnecessarily leak into the user interface.

---

# 12. Morning Check-in

Check-in is optional.

The user chooses which modules participate.

Example:

```text
Good morning

How are you starting your day?

Energy
○ ○ ○ ● ○

Mood
○ ○ ○ ● ○

Stress
○ ● ○ ○ ○

[ Save check-in ]
[ Skip today ]
```

A check-in should normally take less than 15 seconds.

Do not require text.

An optional note may be offered after the primary values.

---

# 13. Evening Check-out

Check-out follows the same interaction model.

Example:

```text
How are you finishing your day?

Energy
○ ○ ● ○ ○

Mood
○ ○ ○ ● ○

Stress
○ ○ ○ ● ○

[ Save check-out ]
[ Skip today ]
```

Do not automatically compare or judge the values in this screen.

---

# 14. Journal View

Journal is the chronological history.

The primary control is the date.

Conceptually:

```text
Journal

‹  Saturday, 15 August  ›

08:15  Check-in
       Energy      4 / 5
       Mood        4 / 5
       Stress      2 / 5

10:32  Hydration
       Large glass

12:04  Break
       Regular break

15:48  Stress
       4 / 5
       Project deadline today

17:21  Check-out
       Energy      3 / 5
       Mood        3 / 5
       Stress      4 / 5

[ Add entry ]
```

---

# 15. Journal Grouping

Entries belonging to the same batch should be visually grouped.

A check-in with three metrics should look like one journal event.

The database still contains three atomic entries.

Likewise a Sleep submission may appear as:

```text
Sleep
7 h 15 min
Recovery 4 / 5
```

rather than two unrelated journal rows.

---

# 16. Journal Editing

Selecting an entry or grouped event opens its details.

The user may:

* edit
* change recorded time
* edit note
* delete

Delete requires confirmation.

Editing should use the same metric input component as creation where practical.

---

# 17. Dates

Journal navigation should make nearby days easy to reach.

The MVP should support:

* previous day
* next day
* date selection
* Today shortcut

Do not build a complex calendar application inside Health.

---

# 18. Former Overview (superseded by Statistics v3)

Overview is for reflection rather than detailed analysis.

It has two initial periods:

```text
Week
Month
```

The default is:

```text
Week
```

Both week and month layouts are derived from:

```text
GET /api/v2/summaries/days?from=&to=
```

The UI does not require separate week or month summary endpoints.

---

# 19. Former Weekly Overview (superseded by Statistics v3)

The weekly view should provide one compact answer:

> How did my week look according to the things I chose to track?

Conceptually:

```text
This week
10 – 16 August

            Mo  Tu  We  Th  Fr  Sa  Su

Stress      ●   ●   ●   ●   ●   –   –
Energy      ●   ●   ●   ●   ●   –   –
Hydration   ●   ●   ●   ●   ●   –   –
Breaks      ●   ●   ●   ●   ●   –   –

Goals
Hydration   4 / 5 tracked days reached
Breaks      3 / 5 tracked days reached

Check-ins   5
Check-outs  4
```

Status indicators combine:

* color
* icon or shape
* accessible text or tooltip

Color must never be the only information carrier.

---

# 20. Status Semantics

Health may use a simple visual status system.

Suggested semantics:

```text
positive
partial
outside target
no data
```

Avoid wording such as:

```text
healthy
unhealthy
good person
bad day
failed
```

where the meaning is not medically justified.

Prefer:

```text
Goal reached
Partially reached
Outside your target
No data
```

The target belongs to the user.

Health does not determine what a medically healthy target should be.

---

# 21. Color Usage

Color is supportive, not authoritative.

Conceptual status colors may resemble:

```text
green   → target reached
yellow  → partially reached
orange  → outside target
gray    → no data
```

The actual implementation must use colors compatible with the Nextcloud design system and accessibility requirements.

Do not hard-code a separate Health design palette when Nextcloud design variables are suitable.

Avoid excessive red.

Health should not visually resemble an alarm or medical warning system.

---

# 22. Former Monthly Overview (superseded by Statistics v3)

Month view provides a compact visual history.

Preferred representation:

```text
August 2026

Mo Tu We Th Fr Sa Su
                1  2
 3  4  5  6  7  8  9
10 11 12 13 14 15 16
17 18 19 20 21 22 23
...
```

Each day may display a small overall tracking status.

Below the calendar:

```text
Hydration
18 / 23 tracked days reached target

Breaks
15 / 23 tracked days reached target

Stress
Average 2.9 / 5

Energy
Average 3.6 / 5
```

The monthly screen remains descriptive.

---

# 23. No Universal Health Score

Health must not calculate a single universal score such as:

```text
Health Score: 73 / 100
```

The metrics represent different concepts and should not be arbitrarily combined into a pseudo-medical number.

The Overview may summarize individual metrics but must not create an overall health rating.

---

# 24. Statistics

Statistics is the detailed analysis view.

It answers:

> How has one metric developed over time?

The user first selects:

```text
Metric
Period
```

Example:

```text
Statistics

Metric
[ Stress ▼ ]

Period
[ 30 days ▼ ]
```

---

# 25. Statistics Header

For Stress:

```text
Stress

30 days

Average       2.8 / 5
Minimum       1
Maximum       5
Entries       26
```

Below:

```text
Metric history chart
```

This information comes directly from the Statistics API.

---

# 26. Chart

Metric history is displayed using Chart.js.

Chart.js is the only charting library permitted.

Initial chart types:

* line chart
* bar chart where appropriate

Examples:

```text
Stress       → line
Energy       → line
Weight       → line
Sleep        → line
Hydration    → bars/count
Breaks       → bars/count
```

Charts must not invent interpolated health measurements that were never recorded.

---

# 27. Missing Data

Missing data must remain visibly missing.

Do not automatically turn:

```text
Monday: 2
Tuesday: no entry
Wednesday: 4
```

into an implied continuous measured Tuesday value.

The visualization may connect or separate points according to Chart.js behavior, but it must not present invented values as actual measurements.

---

# 28. Metric Comparison

Comparing multiple metrics is not part of the first MVP.

Statistics initially displays exactly one atomic metric.

Do not implement correlation analysis.

Do not implement:

```text
Stress vs Sleep
```

until a later product decision.

This keeps the first statistics implementation simple.

---

# 29. Statistics Periods

Initial predefined periods:

```text
7 days
30 days
3 months
1 year
```

A custom date range may be added later.

The UI maps these periods to the public Statistics API.

---

# 30. Settings

Settings contain personal Health configuration.

Initial sections:

```text
Tracking
Check-in & check-out
Goals
Reminders
```

Settings are part of the Health application, not the global Nextcloud administration settings.

---

# 31. Tracking Settings

The user enables or disables modules.

Conceptually:

```text
Tracking

Stress          [ enabled ]
Energy          [ enabled ]
Mood            [ enabled ]
Hydration       [ enabled ]
Breaks          [ enabled ]
Movement        [ disabled ]
Sleep           [ enabled ]
Weight          [ disabled ]
```

Each enabled module may open additional configuration.

---

# 32. Module Configuration

Example Stress:

```text
Stress

Enabled                         [ on ]
Show in quick entry             [ on ]
Include in morning check-in     [ on ]
Include in evening check-out    [ on ]
```

Example Weight:

```text
Weight

Enabled                         [ on ]
Show in quick entry             [ off ]
Include in morning check-in     [ off ]
Include in evening check-out    [ off ]
```

Only sensible options should be offered.

For example, adding Hydration to a morning check-in may not be useful unless the product explicitly supports it.

---

# 33. Display Order

Users may eventually reorder quick-entry modules.

For the first MVP, a simple predefined order is acceptable.

If reordering is implemented, use an accessible Nextcloud-supported interaction.

Do not build complex drag-and-drop solely for cosmetic customization unless required.

---

# 34. Goals Settings

Goals should be understandable in normal language.

Example:

```text
Hydration

Daily goal

[ 6 ] drinks

[ Save ]
```

Example:

```text
Breaks

Daily goal

[ 3 ] breaks
```

Avoid exposing internal fields such as:

```text
goalType = minimum_count
period = day
```

to the user.

Those are API/domain concepts.

---

# 35. Reminders Settings

Reminder setup should remain simple.

Example:

```text
Morning check-in
08:30       [ enabled ]

Hydration reminder
11:00       [ enabled ]

Break reminder
14:00       [ enabled ]

Evening check-out
17:00       [ enabled ]
```

The MVP uses explicitly configured times.

Do not create calendar-like recurrence editors.

---

# 36. Reminder Notification Action

When a reminder appears through Nextcloud Notifications, opening it should lead to the relevant Health action.

Example:

```text
Time for your Health check-in
```

opens Health and focuses the check-in interaction.

Notification content must not contain private metric values.

---

# 37. Onboarding

First launch shows lightweight onboarding.

Goal:

> Configure Health in less than two minutes.

Suggested flow:

## Step 1

```text
What would you like to keep track of?
```

Select modules.

## Step 2

```text
Would you like a short daily check-in?
```

Configure check-in modules.

## Step 3

```text
Would you like reminders?
```

Optional reminder times.

## Finish

```text
Your Health journal is ready.
```

No configuration is permanent.

The user can change everything later.

---

# 38. Recommended Initial Selection

Stress is initially enabled for a new user until persistent module configuration is implemented.

The user remains in control and will be able to change this default through module configuration.

Do not enable extensive tracking by default.

---

# 39. Progressive Disclosure

Do not show every possible feature simultaneously.

Example:

A user who enables only:

```text
Stress
Energy
Breaks
```

should experience Health primarily as an application containing those three concepts.

Do not fill the interface with disabled modules.

---

# 40. Empty States

Empty states should explain the next useful action.

Example Statistics:

```text
No stress entries yet

Record stress a few times to see your history here.

[ Record stress ]
```

Example Journal:

```text
Nothing recorded today

Add an entry whenever you want to keep something in your journal.

[ Add entry ]
```

Use appropriate Nextcloud empty-state components.

---

# 41. Loading States

Do not show blank screens while API data loads.

Use the appropriate Nextcloud loading component.

Loading should be local where possible.

Example:

Changing the Statistics period should update the chart area without unnecessarily replacing the entire application with a global spinner.

---

# 42. Error States

Errors should be:

* short
* actionable where possible
* non-technical

Example:

```text
The entry could not be saved.
Please try again.
```

Do not show:

```text
MapperException
SQLSTATE
HTTP stack trace
```

Frontend errors must not expose backend internals.

---

# 43. Optimistic UI

Avoid complex optimistic updates in the initial MVP.

For important Health entries:

1. send API request
2. confirm success
3. update UI

A quick action should still feel immediate, but correctness is more important than pretending an entry was saved before the server confirmed it.

---

# 44. Accessibility

Health must remain usable:

* with keyboard
* with screen readers
* without relying only on color
* at different browser zoom levels

Interactive elements must have understandable accessible names.

Icons alone must not be used where meaning would otherwise be ambiguous.

---

# 45. Responsive Layout

Health is primarily a Nextcloud web application but must remain usable on smaller screens.

Desktop:

```text
Navigation | Main content
```

Small screens:

```text
Main content
```

with Nextcloud's responsive navigation behavior.

Cards and metric controls should stack vertically where space is limited.

Do not create a second unrelated mobile web design.

---

# 46. Future Native Mobile Apps

Native mobile applications are separate API clients.

The web UI does not need to imitate iOS or Android design.

The public Health API provides the shared product behavior.

Native applications may later implement platform-native interfaces using the same API.

---

# 47. Typography and Spacing

Use Nextcloud design variables and component defaults.

Do not introduce a custom typography system.

Do not manually reproduce Nextcloud spacing when an existing component already provides the correct layout.

Health should visually belong to the surrounding Nextcloud installation.

---

# 48. Icons

Use one consistent icon source compatible with the Nextcloud frontend stack.

Do not mix:

* emoji
* multiple icon frameworks
* custom SVG styles
* random icon sets

Emoji used in product documentation are conceptual only and are not instructions for implementation.

---

# 49. Internationalization

Every user-facing string must be translatable.

Do not hard-code English or German strings directly into components without using the Nextcloud translation mechanism.

This includes:

* labels
* buttons
* empty states
* validation messages
* notification text
* chart labels
* settings descriptions

---

# 50. Health Language

Language should remain neutral.

Prefer:

```text
Record
Track
Your target
Your entry
Your week
Average
No data
```

Avoid:

```text
You failed
Unhealthy
Dangerous
You should
You must improve
```

unless a future medically reviewed feature specifically requires different wording.

Health describes.

The user interprets.

---

# 51. Notes and Privacy

Notes should visually feel personal.

The interface may use a small privacy indicator or explanatory text where appropriate:

```text
Private to your Health account
```

But do not make technically false claims such as:

```text
Even your server administrator can never access this.
```

---

# 52. Destructive Actions

Deleting an entry requires explicit confirmation.

Deleting configuration does not delete historical data.

If a future action deletes all Health data, it must be clearly separated from ordinary settings and require strong confirmation.

---

# 53. Design References

Visual design references belong in:

```text
docs/design/
```

Suggested files:

```text
today.png
statistics.png
```

These images describe:

* information hierarchy
* visual direction
* density
* grouping

They are not pixel-perfect implementation specifications.

When a screenshot conflicts with:

* accessibility
* current Nextcloud component behavior
* responsive layout
* official Nextcloud design conventions

the official Nextcloud conventions take priority.

---

# 54. Custom Health Components

Custom Health Vue components are allowed when they represent Health-specific product concepts.

Initial conceptual components may include:

```text
HealthToday
QuickEntry
MetricInput
ScaleMetricInput
EventMetricInput
SleepInput
CheckinForm
JournalEntry
JournalEntryGroup
GoalStatus
WeeklyMetricStatus
HealthHeatmap
MetricChart
```

These components must compose `@nextcloud/vue` controls where suitable.

They must not evolve into a parallel design system.

---

# 55. View Structure

A possible source structure is:

```text
src/
├── views/
│   ├── TodayView.vue
│   ├── JournalView.vue
│   ├── StatisticsView.vue
│   └── SettingsView.vue
│
├── components/
│   ├── journal/
│   ├── metrics/
│   ├── goals/
│   ├── statistics/
│   └── checkin/
│
└── api/
```

Exact filenames may change during implementation.

Views should remain orchestration components rather than containing all application behavior themselves.

---

# 56. First Implementation Slice

The first UI implementation must not attempt to implement the complete UI specification.

The initial vertical slice is:

```text
Today
  ↓
Quick Entry
  ↓
Stress
  ↓
Select 1–5
  ↓
Save through Health API v2
  ↓
Display saved entry in today's journal
```

This slice establishes:

* application layout
* navigation
* API client
* metric input pattern
* saving
* loading state
* error handling
* journal rendering

Only after this flow is working should additional metrics be added.

---

# 57. UI Definition of Done

A user-facing feature is complete only when:

1. it uses the public Health API
2. standard controls use `@nextcloud/vue`
3. it works with keyboard interaction
4. status is not communicated by color alone
5. all visible strings are translatable
6. loading state exists
7. error state exists
8. empty state exists where appropriate
9. it remains usable at narrow widths
10. it does not expose technical API/domain concepts unnecessarily
11. it does not introduce another UI framework
12. Health-specific data is never sent to an external frontend service
13. every action button has a meaningful label or accessible name
14. core functionality remains reachable when the side navigation is collapsed
15. contextual menus normally contain no more than five actions
16. all core functionality is operable with keyboard navigation

---

# 58. Product UI Principle

When choosing between:

```text
more information
```

and:

```text
less friction
```

prefer less friction for data entry.

When choosing between:

```text
automatic interpretation
```

and:

```text
clear visualization
```

prefer clear visualization.

When choosing between:

```text
custom UI
```

and:

```text
standard Nextcloud UI
```

prefer standard Nextcloud UI.

Health should be easy to enter, easy to leave, and useful when the user returns.

## Journal refinement

Metric identity uses centrally configured, theme-safe colors consistently for icons and scale progress bars. All five implemented metrics are grouped by metric and initially collapsed for each newly loaded Journal date. Scale headers display their accessible average progress bar only after at least one entry is recorded, and event headers retain compact secondary quick actions. Entry metadata appears as noninteractive text at the bottom of the existing action menu, after the icon-labelled Edit and Delete actions.

The Journal uses transient Nextcloud toast feedback for creates, edits, deletes, daily-note saves, and failures. Its plain-text, keyboard-accessible `NcRichContenteditable` daily note occupies the content width, autosaves after 800 ms of inactivity, and never renders or stores HTML. The historical-date Today navigation action is primary, while it remains visible but disabled for today's route.

## Settings profile and units

Settings remains an `NcAppSettingsDialog` with registered application settings sections for Metrics, Profile, and Units. Unit preferences use accessible inline radio controls; fixed units are text. Profile autosaves after an 800 ms pause and includes optional Height, Date of birth, and Sex used for growth reference. The Weight daily-value card may display a neutral derived BMI and a keyboard-accessible details popover, but must not show diagnostic categories. BMI-for-age details require verified bundled WHO reference data and are omitted when unavailable.

## Goals and journal indicators

Goals is a primary application view between Journal and Statistics. It uses URL query state independently for day, week, and month navigation, refuses future periods, and groups goal cards into Daily, Weekly, Monthly, and Long-term sections. The primary New goal action opens an inline, focusable editor using standard Nextcloud controls; it offers only enabled registry targets and exposes pause, edit, retire, comparator, period, target value, and optional Gentle reminders. Journal group headers, daily values, and measurement rows show one compact goal target button where applicable. Its accessible popover is information-only and uses text plus progress bars/status, never color alone. Job Satisfaction is a full-width daily value card with a 1–5 radio editor and neutral progress preview; the normal value remains compact and no numeric scale editor is shown for it.
