/**
 * Prerender script for SSG (Static Site Generation)
 * Generates static HTML for landing pages for better SEO
 */

import Prerenderer from '@prerenderer/prerenderer'
import PuppeteerRenderer from '@prerenderer/renderer-puppeteer'
import path from 'path'
import { fileURLToPath } from 'url'
import fs from 'fs'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const distPath = path.resolve(__dirname, '../dist')

// Landing routes to prerender (16 pages)
const routes = [
  '/',
  '/about',
  // Features
  '/features/voice-control',
  '/features/telegram',
  '/features/ai-assistant',
  '/features/file-processing',
  '/features/smart-reminders',
  '/features/web-interface',
  '/features/web-search',
  // Compare
  '/compare/todoist',
  '/compare/ticktick',
  '/compare/anydo',
  '/compare/google-keep',
  '/compare/things3',
  // Alternatives
  '/alternatives/todoist',
  '/alternatives/google-keep'
]

async function prerender() {
  console.log('🚀 Starting prerendering...')
  console.log(`📁 Static directory: ${distPath}`)
  console.log(`📄 Routes to prerender: ${routes.length}`)

  const prerenderer = new Prerenderer({
    staticDir: distPath,
    renderer: new PuppeteerRenderer({
      // Wait for page content to fully load (loader disappears after ~2.5s)
      renderAfterTime: 5000,
      // Headless Chrome options
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    })
  })

  try {
    await prerenderer.initialize()

    const renderedRoutes = await prerenderer.renderRoutes(routes)

    // Save prerendered HTML
    for (const route of renderedRoutes) {
      const outputPath = path.join(
        distPath,
        route.route === '/' ? 'index.html' : `${route.route}/index.html`
      )

      // Create directory if needed
      const dir = path.dirname(outputPath)
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true })
      }

      // Write HTML file
      fs.writeFileSync(outputPath, route.html)
      console.log(`✅ Prerendered: ${route.route}`)
    }

    console.log(`\n🎉 Successfully prerendered ${renderedRoutes.length} pages!`)
  } catch (error) {
    console.error('❌ Prerender failed:', error)
    process.exit(1)
  } finally {
    await prerenderer.destroy()
  }
}

prerender()
