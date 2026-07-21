# Seeded Scenarios

## Positions

| Code | State | Scenario |
|---|---|---|
| IRAD-SWE-001 | Open | Healthy recruiting pipeline with an assigned candidate and another signed offer |
| IRAD-CYB-002 | Open | Active technical screening |
| IRAD-DOP-003 | In Process | Interview and crossover review underway |
| IRAD-BA-004 | Open | Early-stage candidates |
| IRAD-DBA-005 | Closed | Filled position with an assigned candidate |
| IRAD-NET-006 | Closed | Cancelled customer requirement |
| IRAD-PM-007 | Open | Tech screen and offer activity |
| IRAD-QA-008 | In Process | Active interviews |
| IRAD-DATA-009 | Open | Recently opened position |
| IRAD-SYS-010 | Open | Newly submitted candidate |

## Candidate state distribution

The Candidate model currently validates these high-level states:

- submitted
- selected
- approved
- assigned

Detailed progress and unsuccessful outcomes are represented by workflow events, including cancelled interviews, cancelled technical screens, and denied crossover reviews.
