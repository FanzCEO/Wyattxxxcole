/**
 * WYATT XXX COLE - SEO Configuration
 * Comprehensive SEO, AEO, and structured data configuration
 */

const SITE_CONFIG = {
  name: 'WYATT XXX COLE',
  tagline: 'Country Bred. Fully Loaded.',
  domain: 'wyattxxxcole.com',
  url: 'https://wyattxxxcole.com',
  description: 'WYATT XXX COLE - Country Bred. Fully Loaded. 132+ Five-Star Reviews. Custom content, live sessions, and exclusive southern charm from Alabama\'s favorite redneck bottom boy.',
  keywords: [
    'wyatt xxx cole',
    'adult content creator',
    'custom videos',
    'live sessions',
    'onlyfans',
    'boyfanz',
    'pupfanz',
    'southern adult content',
    'country boy adult',
    'redneck content creator',
    'alabama adult content',
    'custom adult videos',
    'exclusive content',
    'five star reviews'
  ],
  author: 'WYATT XXX COLE',
  locale: 'en_US',
  type: 'website',
  themeColor: '#C68E3F',

  // Social profiles
  social: {
    twitter: '@wyattxxxcole',
    instagram: '@wyattxxxcole',
    boyfanz: 'https://boyfanz.fanz.website/@wyattxxxcole',
    pupfanz: 'https://pupfanz.fanz.website/@wyattxxxcole'
  },

  // Images for sharing
  ogImage: 'https://wyattxxxcole.com/images/og-image.jpg',
  twitterImage: 'https://wyattxxxcole.com/images/twitter-card.jpg',

  // Contact
  email: 'contact@wyattxxxcole.com',
  businessEmail: 'business@wyattxxxcole.com',
  location: 'Alabama, USA'
};

const PAGES = {
  home: {
    title: 'WYATT XXX COLE | Country Bred. Fully Loaded.',
    description: 'WYATT XXX COLE - Country Bred. Fully Loaded. 132+ Five-Star Reviews. Custom content, live sessions, and exclusive southern charm from Alabama\'s favorite redneck bottom boy.',
    path: '/',
    priority: 1.0,
    changefreq: 'weekly'
  },
  gallery: {
    title: 'Gallery | WYATT XXX COLE',
    description: 'Browse the exclusive gallery of WYATT XXX COLE. Preview content and get a taste of what\'s waiting for you on premium platforms.',
    path: '/gallery.html',
    priority: 0.9,
    changefreq: 'daily'
  },
  booking: {
    title: 'Book Me | WYATT XXX COLE',
    description: 'Book custom videos, live sessions, ratings, and sexting with WYATT XXX COLE. Starting from $25. Personalized adult content tailored to you.',
    path: '/booking.html',
    priority: 0.9,
    changefreq: 'weekly'
  },
  subscribe: {
    title: 'Subscribe | WYATT XXX COLE',
    description: 'Subscribe to WYATT XXX COLE for exclusive content, behind-the-scenes access, and direct messaging. Multiple subscription tiers available.',
    path: '/subscribe.html',
    priority: 0.8,
    changefreq: 'weekly'
  },
  world: {
    title: 'World | WYATT XXX COLE',
    description: 'Explore the world of WYATT XXX COLE. Collaborations, features, and the network of platforms where you can find exclusive content.',
    path: '/world.html',
    priority: 0.7,
    changefreq: 'monthly'
  },
  contact: {
    title: 'Contact | WYATT XXX COLE',
    description: 'Get in touch with WYATT XXX COLE for business inquiries, collaborations, press, or fan messages. Typical response within 24-48 hours.',
    path: '/contact.html',
    priority: 0.7,
    changefreq: 'monthly'
  },
  merch: {
    title: 'Merch Store | WYATT XXX COLE',
    description: 'Official WYATT XXX COLE merchandise. Hats, shirts, and gear with that country boy swagger. Rep the brand.',
    path: '/merch.html',
    priority: 0.8,
    changefreq: 'weekly'
  },
  terms: {
    title: 'Terms of Service | WYATT XXX COLE',
    description: 'Terms of Service and user agreement for wyattxxxcole.com',
    path: '/terms.html',
    priority: 0.3,
    changefreq: 'yearly'
  },
  privacy: {
    title: 'Privacy Policy | WYATT XXX COLE',
    description: 'Privacy Policy for wyattxxxcole.com - How we collect, use, and protect your information.',
    path: '/privacy.html',
    priority: 0.3,
    changefreq: 'yearly'
  },
  dmca: {
    title: 'DMCA | WYATT XXX COLE',
    description: 'DMCA policy and content takedown procedures for wyattxxxcole.com',
    path: '/dmca.html',
    priority: 0.2,
    changefreq: 'yearly'
  },
  compliance: {
    title: '18 U.S.C. 2257 Compliance | WYATT XXX COLE',
    description: '18 U.S.C. 2257 record-keeping compliance statement for wyattxxxcole.com',
    path: '/2257.html',
    priority: 0.2,
    changefreq: 'yearly'
  }
};

// Structured Data Schemas
const SCHEMAS = {
  // Person Schema for Wyatt
  person: {
    '@context': 'https://schema.org',
    '@type': 'Person',
    'name': 'WYATT XXX COLE',
    'alternateName': 'WXXXC',
    'description': 'Adult content creator from Alabama. Country Bred. Fully Loaded. 132+ Five-Star Reviews.',
    'url': 'https://wyattxxxcole.com',
    'image': 'https://wyattxxxcole.com/images/og-image.jpg',
    'sameAs': [
      'https://twitter.com/wyattxxxcole',
      'https://instagram.com/wyattxxxcole',
      'https://boyfanz.fanz.website/@wyattxxxcole',
      'https://pupfanz.fanz.website/@wyattxxxcole'
    ],
    'knowsAbout': [
      'Adult content creation',
      'Custom video production',
      'Live streaming',
      'Content monetization'
    ],
    'homeLocation': {
      '@type': 'Place',
      'name': 'Alabama, USA'
    }
  },

  // WebSite Schema
  website: {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    'name': 'WYATT XXX COLE',
    'alternateName': 'WXXXC',
    'url': 'https://wyattxxxcole.com',
    'description': 'Official website of WYATT XXX COLE - Country Bred. Fully Loaded.',
    'publisher': {
      '@type': 'Person',
      'name': 'WYATT XXX COLE'
    },
    'potentialAction': {
      '@type': 'SearchAction',
      'target': 'https://wyattxxxcole.com/search?q={search_term_string}',
      'query-input': 'required name=search_term_string'
    }
  },

  // Service Schema for Booking
  services: {
    '@context': 'https://schema.org',
    '@type': 'Service',
    'serviceType': 'Adult Content Creation',
    'provider': {
      '@type': 'Person',
      'name': 'WYATT XXX COLE'
    },
    'name': 'Custom Content & Live Sessions',
    'description': 'Custom videos, live sessions, ratings, and sexting services.',
    'offers': [
      {
        '@type': 'Offer',
        'name': 'Custom Videos',
        'description': '5-10 minute custom videos with 48hr delivery',
        'price': '50.00',
        'priceCurrency': 'USD',
        'priceValidUntil': '2025-12-31'
      },
      {
        '@type': 'Offer',
        'name': 'Live Sessions',
        'description': '15-30 minute private live video sessions',
        'price': '100.00',
        'priceCurrency': 'USD',
        'priceValidUntil': '2025-12-31'
      },
      {
        '@type': 'Offer',
        'name': 'Ratings',
        'description': 'Honest text or video ratings with 24hr turnaround',
        'price': '25.00',
        'priceCurrency': 'USD',
        'priceValidUntil': '2025-12-31'
      },
      {
        '@type': 'Offer',
        'name': 'Sexting',
        'description': 'Real-time messaging with photos',
        'price': '30.00',
        'priceCurrency': 'USD',
        'priceValidUntil': '2025-12-31'
      }
    ],
    'areaServed': 'Worldwide',
    'url': 'https://wyattxxxcole.com/booking.html'
  },

  // FAQ Schema for AEO
  faq: {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    'mainEntity': [
      {
        '@type': 'Question',
        'name': 'What services does WYATT XXX COLE offer?',
        'acceptedAnswer': {
          '@type': 'Answer',
          'text': 'WYATT XXX COLE offers custom videos starting at $50, live sessions from $100, ratings from $25, and sexting sessions at $30 per 30 minutes. All content is personalized and delivered with that signature southern charm.'
        }
      },
      {
        '@type': 'Question',
        'name': 'How do I book a session with WYATT XXX COLE?',
        'acceptedAnswer': {
          '@type': 'Answer',
          'text': 'Visit the booking page at wyattxxxcole.com/booking, select your service, choose your preferred date and time (for live sessions), fill in your details and request specifics, then submit. You\'ll receive a confirmation and pricing within 24 hours.'
        }
      },
      {
        '@type': 'Question',
        'name': 'What platforms is WYATT XXX COLE on?',
        'acceptedAnswer': {
          '@type': 'Answer',
          'text': 'WYATT XXX COLE is active on Boyfanz, PupFanz, Twitter/X, and Instagram. Subscribe for exclusive content and direct messaging access.'
        }
      },
      {
        '@type': 'Question',
        'name': 'How long does custom content take to deliver?',
        'acceptedAnswer': {
          '@type': 'Answer',
          'text': 'Custom videos are typically delivered within 48 hours. Ratings have a 24-hour turnaround. Live sessions are scheduled at your convenience based on availability.'
        }
      },
      {
        '@type': 'Question',
        'name': 'Is all content 18+ only?',
        'acceptedAnswer': {
          '@type': 'Answer',
          'text': 'Yes, all content and services are strictly for adults 18 years and older. Age verification is required for all purchases and subscriptions.'
        }
      }
    ]
  },

  // Breadcrumb Schema
  breadcrumb: (page, title) => ({
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    'itemListElement': [
      {
        '@type': 'ListItem',
        'position': 1,
        'name': 'Home',
        'item': 'https://wyattxxxcole.com'
      },
      {
        '@type': 'ListItem',
        'position': 2,
        'name': title,
        'item': `https://wyattxxxcole.com${page}`
      }
    ]
  }),

  // Review Aggregate
  reviews: {
    '@context': 'https://schema.org',
    '@type': 'AggregateRating',
    'itemReviewed': {
      '@type': 'Person',
      'name': 'WYATT XXX COLE'
    },
    'ratingValue': '5',
    'ratingCount': '132',
    'bestRating': '5',
    'worstRating': '1'
  }
};

// Industry Backlinks (from FANZ network)
const BACKLINKS = [
  { name: 'XBIZ', url: 'https://xbiz.com', category: 'industry_news' },
  { name: 'AVN', url: 'https://avn.com', category: 'industry_news' },
  { name: 'YNOT', url: 'https://ynotcam.com', category: 'industry_news' },
  { name: 'CCBill', url: 'https://ccbill.com', category: 'payments' },
  { name: 'Segpay', url: 'https://segpay.com', category: 'payments' },
  { name: 'DMCA.com', url: 'https://dmca.com', category: 'content_protection' },
  { name: 'RTA', url: 'https://rtalabel.org', category: 'compliance' },
  { name: 'ASACP', url: 'https://asacp.org', category: 'compliance' },
  { name: 'BoyFanz', url: 'https://boyfanz.fanz.website', category: 'platform' },
  { name: 'PupFanz', url: 'https://pupfanz.fanz.website', category: 'platform' },
  { name: 'FANZ Network', url: 'https://fanz.website', category: 'platform' }
];

// Export for use
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { SITE_CONFIG, PAGES, SCHEMAS, BACKLINKS };
}
