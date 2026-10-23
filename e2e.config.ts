import type { E2EConfig } from 'e2e';
import { web } from '@e2e-dev/web';
import { opencodeConsole } from 'e2e/oauth/opencode-console';

export default {
  // OpenCode Console subscription: sign in with `npx e2e login opencode-console`; `npx e2e models opencode-console` lists ids.
  agents: {
    default: {
      model: opencodeConsole('go/deepseek-v4.1-flash'),
      system: 'You are a thorough QA agent testing the Filament admin dashboard. Verify every outcome.',
    },
  },
  // Dashboard (/admin) and REST API (/api/v1) only, served by Herd.
  credentials: {
    admin: {
      username: process.env.E2E_ADMIN_EMAIL ?? 'admin@example.test',
      password: () => process.env.E2E_ADMIN_PASSWORD ?? '',
    },
  },
  targets: [{
    engine: web(),
    app: { url: process.env.APP_URL ?? 'https://thop.test' },
  }],
} satisfies E2EConfig;
