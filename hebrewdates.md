We are currently in the middle of working on the 5 module system (see plan.txt for full details)
Right now I want module 1 which is for attendance to be displaying for all users in Hebrew dates.
## The Goal
I need to refactor the application so that:
1. **Module 1 (Attendance & Reports)** displays **ONLY** Hebrew dates (no English dates visible to the user).
2. **Modules 2-5 (Admissions, Tuition, Payroll, CRM)** continue to display English dates.
3. The underlying database continues to store all dates in standard Gregorian/UTC format to ensure data integrity across modules.

## Scope of Work (Reference)
1. Attendance & Reports (HEBREW DATES ONLY)
   - Teacher interface, Admin oversight, Reporting, History logs.
2. Admissions (English Dates)
3. Tuition (English Dates)
4. Payroll (English Dates)
5. Donor CRM (English Dates)

## Your Task
Don't write the implementation code yet. I need you to first analyze this requirement and propose a technical plan.

Please output a response covering:
1. **Library Strategy:** currently I believe we are calling the Hebcal API. Is this scalable for a report with 50 rows? Should we switch to a client-side library (like @hebcal/core) for synchronous conversion?
2. **Architecture Options:** Propose 2 ways to implement this "module-scoped" date formatting (e.g., React Context vs. Route-based logic vs. Component props).
3. **Edge Case Analysis:** How will we handle:
   - **Date Pickers:** When a teacher selects a date for attendance, should the picker UI be Hebrew or English? How do we convert that selection back to the database?
   - **Sorting:** If the UI shows Hebrew months (Tishrei, Cheshvan), how do we ensure the tables still sort chronologically?

Once you have outlined these options and your recommendation, stop and wait for my approval before generating the code.

