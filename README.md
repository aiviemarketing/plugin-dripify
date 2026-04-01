# Aivie Dripify Bundle

Aivie plugin that exposes a public webhook for importing Dripify contacts into Aivie or Mautic.

## What It Does

- Provides a public `POST` webhook at `/dripify/webhook`
- Accepts a Dripify contact payload as JSON
- Uses the first valid email as the primary contact identifier
- Creates the contact if it does not exist
- Updates the existing contact if it already exists
- Maps company website and industry onto the related company record

## Installation

Copy the plugin into your Mautic `plugins/` directory as `AivieDripifyBundle`, then install or refresh plugins from the Mautic/Aivie admin interface.

After installation, open the `Dripify` integration in the admin UI and publish it. The webhook endpoint is only available while the integration is published.

## Webhook

- Method: `POST`
- Path: `/dripify/webhook`
- Content-Type: `application/json`

Example request:

```json
{
  "firstName": "Bill",
  "lastName": "Gates",
  "location": "Seattle Washington",
  "city": "Seattle",
  "country": "USA",
  "premium": "Yes",
  "link": "https://www.linkedin.com/in/williamhgates/",
  "website": "https://www.gatesnotes.com/",
  "email": "b.gates@microsoft.com",
  "manualEmail": "b.gates.manual@microsoft.com",
  "corporateEmail": "b.gates.corporate@microsoft.com",
  "linkedInEmail": "b.gates.linkedin@microsoft.com",
  "phone": "+845-476-0128",
  "company": "Bill & Melinda Gates Foundation",
  "companyWebsite": "https://www.gatesnotes.com/",
  "position": "Co-chair",
  "industry": "Charity",
  "education": "Harvard University",
  "hookDate": "01/04/2026",
  "numberOfCompanyEmployees": "120",
  "numberOfCompanyFollowers": "750"
}
```

Successful responses return JSON with:

- `success`
- `action` as `created` or `updated`
- `contactId`
- `message`

## Field Mapping

| Dripify field | Aivie field | Notes |
| --- | --- | --- |
| `firstName` | `firstname` | Direct mapping |
| `lastName` | `lastname` | Direct mapping |
| `location` | `dripify_location` | Custom lead field |
| `city` | `city` | Direct mapping |
| `country` | `country` | Direct mapping |
| `premium` | `dripify_premium` | `Yes`/`No` becomes `1`/`0` |
| `link` | `linkedin` | Direct mapping |
| `website` | `website` | Direct mapping |
| `email` | `email` | First choice for the primary identifier |
| `manualEmail` | `dripify_manual_email` | Second email fallback |
| `corporateEmail` | `dripify_corporate_email` | Third email fallback |
| `linkedInEmail` | `dripify_linkedin_email` | Fourth email fallback |
| `phone` | `phone` | Direct mapping |
| `company` | `company` | Also used to identify the related company |
| `companyWebsite` | `companywebsite` | Saved on the associated company record |
| `position` | `position` | Direct mapping |
| `industry` | `companyindustry` | Saved on the associated company record |
| `education` | `education` | Custom lead field |
| `hookDate` | `dripify_hook_date` | Converts `DD/MM/YYYY` to `YYYY-MM-DD` |
| `numberOfCompanyEmployees` | `dripify_company_employees` | Numeric string cast to integer string |
| `numberOfCompanyFollowers` | `dripify_company_followers` | Numeric string cast to integer string |

## Email Fallback Order

The webhook picks the first valid email in this order and stores it as the contact's primary `email`:

1. `email`
2. `manualEmail`
3. `corporateEmail`
4. `linkedInEmail`

## Documentation

The OpenAPI-style webhook reference is available in `Docs/dripify-webhook.yaml`.

## Commit Linting

This plugin includes the same `commitlint` setup used in other Aivie projects.

Install the Node dev dependencies in the plugin directory and lint commit messages with:

```bash
npm install
npx commitlint --edit .git/COMMIT_EDITMSG
```

The configuration lives in `commitlint.config.cjs`.

## Open Source License

This plugin is licensed under `GPL-3.0-or-later` with an attribution notice for Aivie. If you redistribute this plugin or publish modified versions, you must preserve the Aivie copyright and attribution notices.

See `LICENSE` and `NOTICE` for details.

## Attribution

Originally developed by <a href="https://aivie.ch/en/?utm_source=github&utm_medium=recaptcha&utm_campaign=opensource&utm_content=contact>">Aivie</a><br>
<a href="https://aivie.ch/en/?utm_source=github&utm_medium=dripify&utm_campaign=opensource&utm_content=contact>">
  <img width="200px" src="https://cdn.aivie.ch/media/wp/2021/06/19131704/logo-aivie-fast-kein-rand-400w.png"></img></a>