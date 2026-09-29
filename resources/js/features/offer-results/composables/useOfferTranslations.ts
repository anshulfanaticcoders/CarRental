import { usePage } from '@inertiajs/vue3'

type OfferPageProps = {
  locale?: string
  translations?: { offerresults?: Record<string, string> }
}

export type TranslateOffer = (key: string, fallback: string, replacements?: Record<string, string | number>) => string

export const useOfferTranslations = () => {
  const page = usePage<OfferPageProps>()

  const t: TranslateOffer = (key, fallback, replacements = {}) => {
    let translation = page.props.translations?.offerresults?.[key] || fallback

    Object.entries(replacements).forEach(([token, value]) => {
      translation = translation.replace(`:${token}`, `${value}`)
    })

    return translation
  }

  return { t }
}
