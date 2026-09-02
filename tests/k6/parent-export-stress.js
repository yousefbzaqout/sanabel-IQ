/**
 * k6 load test: concurrent parent analytics export generation (PDF + Excel).
 *
 * Usage:
 *   k6 run tests/k6/parent-export-stress.js \
 *     -e BASE_URL=http://localhost \
 *     -e PARENT_EMAIL=loadtest@example.test \
 *     -e PARENT_PASSWORD=password \
 *     -e STUDENT_ID=1
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 50,
  duration: '45s',
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    http_req_failed: ['rate<0.05'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const PARENT_EMAIL = __ENV.PARENT_EMAIL || 'loadtest@example.test';
const PARENT_PASSWORD = __ENV.PARENT_PASSWORD || 'password';
const STUDENT_ID = __ENV.STUDENT_ID || '1';

function extractCsrfToken(html) {
  const match = html.match(/name="csrf-token" content="([^"]+)"/);

  return match ? match[1] : null;
}

function loginAndSelectStudent() {
  const loginPage = http.get(`${BASE_URL}/login`);
  const csrfToken = extractCsrfToken(loginPage.body);

  const loginResponse = http.post(
    `${BASE_URL}/login`,
    {
      email: PARENT_EMAIL,
      password: PARENT_PASSWORD,
      _token: csrfToken,
    },
    {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      redirects: 0,
    },
  );

  const sessionCookie = loginResponse.cookies['laravel_session']?.[0]?.value;

  if (!sessionCookie) {
    throw new Error('Missing laravel_session cookie after login.');
  }

  http.post(
    `${BASE_URL}/students/${STUDENT_ID}/select`,
    {
      _token: csrfToken,
    },
    {
      headers: {
        Cookie: `laravel_session=${sessionCookie}`,
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      redirects: 0,
    },
  );

  return sessionCookie;
}

export function setup() {
  return {
    sessionCookie: loginAndSelectStudent(),
  };
}

export default function (data) {
  const headers = {
    Cookie: `laravel_session=${data.sessionCookie}`,
  };

  const pdfResponse = http.get(
    `${BASE_URL}/parent/analytics/${STUDENT_ID}/export/pdf`,
    { headers },
  );

  check(pdfResponse, {
    'pdf export generated': (response) => response.status === 200,
    'pdf content type': (response) =>
      String(response.headers['Content-Type']).includes('application/pdf'),
  });

  const excelResponse = http.get(
    `${BASE_URL}/parent/analytics/${STUDENT_ID}/export/excel`,
    { headers },
  );

  check(excelResponse, {
    'excel export generated': (response) => response.status === 200,
    'excel content type': (response) =>
      String(response.headers['Content-Type']).includes(
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ),
  });

  sleep(0.2);
}
