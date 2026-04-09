# Skyscanner Technical Survey Answer Sheet

Case ID: `PSM-46100`

Purpose:

- use this as the working draft for the Skyscanner technical survey
- fill only confirmed answers
- do not guess partner-specific answers that still need client or Skyscanner confirmation

## 1. Known Details From Client / Screenshot

These values were already provided by the client:

- Company name: `Multigreen group`
- Website URL: `https://vrooem.com/`

## 2. Answer Sheet

Use this format while filling the survey.

### Question 1. Please state the company type

Status: `Pending client confirmation`

Notes:

- the screenshot shows the company-type options
- no selected option is visible in the screenshot
- do not guess this answer

Recommended action:

- confirm the exact business type with client before submission

### Question 2. What is the name of your company?

Answer:

`Multigreen group`

Status: `Ready`

Source:

- client screenshot

### Question 3. Please provide URL to your website

Answer:

`https://vrooem.com/`

Status: `Ready`

Source:

- client screenshot

### Question 4. Please provide a link to your API documentation

Draft answer:

`API documentation will be shared directly by email with Skyscanner Partner Support.`

Status: `Ready for email-based submission`

Notes:

- the survey text itself says documentation can be sent by email if not available online
- we currently have internal/local documentation and implementation notes
- if a public/online doc link is created later, replace this answer with that URL

### Question 5. Please provide name and contact email for a technical contact

Status: `Pending final confirmation`

What to fill:

- technical contact name
- technical contact email

Notes:

- this should be the person Skyscanner can contact for implementation/testing questions

### Question 6. Please provide name and contact email for a commercial contact

Status: `Pending final confirmation`

What to fill:

- commercial/business contact name
- commercial/business contact email

## 3. Technical Answers We Can Support From Our Side

These are not directly from the screenshot, but they are already confirmed from our implementation and can be used wherever the survey asks related technical questions.

### Integration approach

- Skyscanner integration is being built as a separate isolated flow
- current live platform flow is not being disturbed

### Inventory scope

- current rollout assumption: `internal inventory only`
- final confirmation still required before submission

### Search API status

- isolated local search API is working
- authenticated by API key header

### Authentication model

- header-based API key authentication
- header name: `x-api-key`

### Redirect handling

- redirect flow is implemented locally
- signed redirect validation is implemented locally

### Quote handling

- quote creation works
- quote expiry works
- expired quotes correctly return `quote_expired`

### Tracking / correlation

- redirect ID correlation works locally
- booking correlation works locally
- report-row/export foundation works locally

## 4. Answers Still Pending From Client Or Skyscanner

These must stay pending until confirmed.

### Client-side pending

- exact company type
- technical contact details
- commercial contact details
- confirmation whether keyword tracking is commercially enabled
- final confirmation that first rollout scope is internal inventory only

### Skyscanner-side pending

- final partner-specific request contract
- final partner-specific response contract
- DV implementation docs
- final reporting delivery method
- final testing/go-live process after survey review

## 5. Safe Submission Rule

Before submitting the survey:

- submit only confirmed business details
- use verified technical answers from our side
- leave uncertain items pending until confirmed
- do not assume final contract fields that Skyscanner has not yet shared

## 6. Current Practical Verdict

We are ready to prepare the survey draft.

We are not ready to guess the unanswered business or partner-specific implementation fields.
