Convert this project into a Progressive Web App (PWA).

PURPOSE:
Teachers take attendance from phones with restricted browsers. 
They will install this as an APK (built later via PWABuilder.com).

SCOPE:
- ONLY the attendance-taking functionality needs to be in the PWA
- Teachers/attendance takers are the only users of this PWA
- Do NOT include admin panels, student management, reports, or other features
- This is a focused, minimal app just for taking attendance

REQUIREMENTS:

1. Web App Manifest
   - App name: "Attendance"
   - display: standalone
   - Icons: 192px and 512px PNG

2. Service Worker
   - Minimal - just enough to be installable
   - Network-first (no caching of attendance data)

3. Persistent Login
   - Keep users logged in between sessions
   - Store auth token so they don't re-login each time

4. PWABuilder Compatibility
   - Ensure manifest and service worker pass PWABuilder validation

DO NOT:
- Add offline mode
- Cache API data
- Include any features beyond attendance-taking
- Modify existing app logic for other parts of the system