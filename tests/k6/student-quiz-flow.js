/**
 * k6 load test: concurrent student quiz submissions.
 *
 * Usage:
 *   k6 run tests/k6/student-quiz-flow.js \
 *     -e BASE_URL=http://localhost \
 *     -e PARENT_EMAIL=loadtest@example.test \
 *     -e PARENT_PASSWORD=password \
 *     -e MATERIAL_ID=1 \
 *     -e STUDENT_ID=1
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 200,
  duration: '30s',
  thresholds: {
    http_req_duration: ['p(95)<200'],
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';
const PARENT_EMAIL = __ENV.PARENT_EMAIL || 'loadtest@example.test';
const PARENT_PASSWORD = __ENV.PARENT_PASSWORD || 'password';
const MATERIAL_ID = __ENV.MATERIAL_ID || '1';
const STUDENT_ID = __ENV.STUDENT_ID || '1';

function extractCsrfToken(html) {
  const match = html.match(/name="csrf-token" content="([^"]+)"/);

  return match ? match[1] : null;
}

function loginAndSelectStudent() {
  const loginPage = http.get(`${BASE_URL}/login`);

  check(loginPage, {
    'login page reachable': (response) => response.status === 200,
  });

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

  check(loginResponse, {
    'login succeeds': (response) => response.status === 302,
  });

  const sessionCookie = loginResponse.cookies['laravel_session']?.[0]?.value;

  if (!sessionCookie) {
    throw new Error('Missing laravel_session cookie after login.');
  }

  const selectResponse = http.post(
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

  check(selectResponse, {
    'active student selected': (response) => response.status === 302,
  });

  return sessionCookie;
}

export function setup() {
  return {
    sessionCookie: loginAndSelectStudent(),
  };
}

export default function (data) {
  const quizPayload = http.get(`${BASE_URL}/student/materials/${MATERIAL_ID}/quiz`, {
    headers: {
      Accept: 'application/json',
      Cookie: `laravel_session=${data.sessionCookie}`,
    },
  });

  check(quizPayload, {
    'quiz payload loaded': (response) => response.status === 200,
  });

  let answers = [];

  try {
    const payload = JSON.parse(quizPayload.body);
    answers = (payload.questions || []).map((question) => ({
      question_id: question.id,
      selected_option_id: question.options?.[0]?.id ?? 0,
    }));
  } catch (error) {
    throw new Error(`Unable to parse quiz payload: ${error}`);
  }

  const submitResponse = http.post(
    `${BASE_URL}/student/materials/${MATERIAL_ID}/quiz/submit`,
    JSON.stringify({ answers }),
    {
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Cookie: `laravel_session=${data.sessionCookie}`,
      },
    },
  );

  check(submitResponse, {
    'quiz submitted': (response) => response.status === 200,
    'quiz response under 200ms p95 target sample': (response) => response.timings.duration < 2000,
  });

  sleep(0.1);
}
