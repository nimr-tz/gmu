<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\User;
use App\Services\SeedGrant\Workflow;
use App\Support\CallForm;
use App\Support\SeedGrant as S;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demonstration data: three calls with different settings, and applications
 * moved through the real workflow to every status, so each page, list and
 * dashboard has something to show. Not run by default:
 *
 *   php artisan db:seed --class=SeedGrantDemoSeeder
 *
 *   2627    Seed grant FY 2026/27       open    the seed grant as written
 *   2627PB  Policy and practice briefs  open    no mentor, no years limit, CV only,
 *                                               four criteria, pass mark 60
 *   2526LI  Laboratory innovation fund  closed  last year's call, finished outcomes;
 *                                               8-year limit, 40% equipment, innovation weighted 20
 *
 * Demo people use @demo.nimr.or.tz addresses and the password "demo-password".
 * Never run it on production data.
 */
class SeedGrantDemoSeeder extends Seeder
{
    private Workflow $workflow;
    private User $gmu;
    private ?User $drcp;
    private ?User $dg;
    private array $reviewers;

    public function run(Workflow $workflow): void
    {
        $this->workflow = $workflow;

        if (SeedApplication::whereHas('call', fn ($q) => $q->whereIn('code', ['2627', '2627PB', '2526LI']))->exists()) {
            $this->command?->warn('The demo calls already have applications; demo data not added.');

            return;
        }

        $this->gmu  = User::whereHas('role', fn ($q) => $q->where('name', 'coordinator'))->firstOrFail();
        $this->drcp = User::whereHas('role', fn ($q) => $q->where('name', 'drcp'))->first();
        $this->dg   = User::whereHas('role', fn ($q) => $q->where('name', 'dg'))->first();
        $this->reviewers = [
            $this->person('Dr Peter Mboya', 'p.mboya', 'research_scientist', 'Muhimbili Centre', 9),
            $this->person('Dr Sara Kimaro', 's.kimaro', 'research_scientist', 'Tanga Centre', 12),
            $this->person('Dr Anna Mkumbo', 'a.mkumbo', 'research_scientist', 'Mwanza Centre', 14),
            $this->person('Dr Musa Mwita', 'm.mwita', 'research_scientist', 'Amani Centre', 10),
        ];

        [$seed, $briefs, $lab] = $this->calls();

        // ── 2627: the seed grant, open, applications at every stage ─────────
        foreach ([
            ['Dr Neema Mushi', 'n.mushi', 'Mwanza Centre', 'Nurse-led follow-up calls to improve retention in HIV care', S::WITH_DRCP],
            ['Dr Elia Lyimo', 'e.lyimo', 'Mbeya Medical Research Centre', 'Task-shifting depression screening to antenatal nurses', S::CONCEPT_REVISION],
            ['Dr Asha Mwakyusa', 'a.mwakyusa', 'Muhimbili Centre', 'Community health worker referral for hypertension in Kinondoni', S::CONCEPT_REVIEW],
            ['Dr Baraka Mollel', 'b.mollel', 'Tanga Centre', 'SMS reminders for malaria RDT use in Muheza dispensaries', S::SUBMITTED],
            ['Dr Grace Massawe', 'g.massawe', 'Mwanza Centre', 'Fidelity of TB contact tracing in Ilemela', S::DRAFT],
            ['Dr Joel Mrema', 'j.mrema', 'Amani Centre', 'Uptake of HPV self-sampling at reproductive health clinics', 'returned'],
            ['Dr Rehema Juma', 'r.juma', 'Dodoma Centre', 'Pharmacy-led hypertension follow-up in Dodoma city', S::SCREENED],
            ['Dr Daudi Kweka', 'd.kweka', 'Tukuyu Centre', 'Integrating diabetes screening into TB clinics', S::SHORTLISTED],
            ['Dr Mwanaisha Ally', 'm.ally', 'Tabora Centre', 'Community follow-up of children discharged after severe malaria', S::PROPOSAL_REVIEW],
            ['Dr Paul Shayo', 'p.shayo', 'Ngongongare Centre', 'Mentorship visits to improve neonatal resuscitation practice', S::WITH_DG],
            ['Dr Zuhura Hamisi', 'z.hamisi', 'Headquarters', 'Electronic referral feedback between dispensaries and hospitals', S::NOT_RECOMMENDED],
            ['Dr Kelvin Massawe', 'k.massawe', 'Muhimbili Centre', 'Clinic-based alcohol screening in HIV care', S::INELIGIBLE],
        ] as [$name, $handle, $centre, $title, $target]) {
            $this->story($seed, $this->person($name, $handle, 'pi', $centre, 3), $title, $target);
        }

        // ── 2627PB: policy and practice briefs, open, a different form ─────
        foreach ([
            ['Dr Neema Mushi', 'n.mushi', 'Mwanza Centre', 'Brief: scaling nurse-led adherence calls in council HIV services', S::RECOMMENDED],
            ['Dr Halima Said', 'h.said', 'Tanga Centre', 'Brief: referral targets for hypertension in primary care', S::PROPOSAL_SUBMITTED],
            ['Dr Ibrahim Kombo', 'i.kombo', 'Headquarters', 'Brief: using DHIS2 data for malaria commodity planning', S::CONCEPT_REVIEW],
            ['Dr Lucy Mtei', 'l.mtei', 'Amani Centre', 'Brief: maternal death review follow-up in district councils', S::SUBMITTED],
            ['Dr Omari Hassan', 'o.hassan', 'Mbeya Medical Research Centre', 'Brief: TB preventive therapy for household contacts', S::APPROVED],
        ] as [$name, $handle, $centre, $title, $target]) {
            // Long-serving staff can apply here: this call has no years limit.
            $this->story($briefs, $this->person($name, $handle, 'pi', $centre, $handle === 'h.said' ? 11 : 3), $title, $target);
        }

        // ── 2526LI: last year's laboratory innovation fund, finished ────────
        foreach ([
            ['Dr Ruth Ngowi', 'r.ngowi', 'Muhimbili Centre', 'Low-cost viral load sample transport for remote clinics', S::AWARDED],
            ['Dr Samuel Temu', 's.temu', 'Mwanza Centre', 'Point-of-care haemoglobin testing in antenatal clinics', S::AGREEMENT_ACCEPTED],
            ['Dr Agnes Lema', 'a.lema', 'Tanga Centre', 'Solar cold-chain monitoring for laboratory reagents', S::AGREEMENT_ISSUED],
            ['Dr Peter Nyamhanga', 'p.nyamhanga', 'Dodoma Centre', 'A smartphone reader for malaria rapid tests', S::CONCEPT_DECLINED],
            ['Dr Emmanuel Kibona', 'e.kibona', 'Tukuyu Centre', 'Paper-based sickle cell screening for newborns', S::PROPOSAL_REVISION],
        ] as [$name, $handle, $centre, $title, $target]) {
            $this->story($lab, $this->person($name, $handle, 'pi', $centre, 6), $title, $target);
        }

        // ── Many more, so the lists, queues and ranking look like a real call ──
        $this->crowd($seed, 3, [
            S::DRAFT => ['Peer support groups to improve PrEP continuation among adolescent girls in Shinyanga', 'Facility checklists to improve postnatal care quality in Kigoma', 'Community drug distributors to sustain deworming coverage in Lindi', 'Text-message follow-up to improve sputum return in presumptive TB'],
            S::SUBMITTED => ['Village health days to raise measles second-dose coverage in Simiyu', 'Nurse-led blood pressure clubs to improve control in Moshi', 'School eye screening to increase spectacle uptake in Arusha', 'Radio drama to improve care-seeking for childhood pneumonia in Mtwara', 'Lay counsellors to deliver brief alcohol interventions in Mbeya', 'Point-of-care CD4 testing to speed advanced HIV disease care in Iringa'],
            'returned' => ['Dispensary stock dashboards to reduce malaria test stock-outs in Kagera', 'Community midwives to improve birth preparedness in Katavi'],
            S::INELIGIBLE => ['Digital adherence technology to support TB treatment in Ilala'],
            S::SCREENED => ['Mobile outreach to increase cervical screening in rural Morogoro', 'Supportive supervision to improve IMCI practice in Singida', 'Linkage officers to speed ART start after community testing in Geita', 'Family planning counselling during child immunisation visits in Rukwa'],
            S::CONCEPT_REVIEW => ['School bed net distribution to sustain coverage in Ruvuma', 'Clinical mentorship to improve sepsis care in district hospitals', 'WhatsApp groups to support community health workers in Njombe', 'Kangaroo mother care champions to improve uptake in Mara', 'Community pharmacists to screen for diabetes in Mwanza', 'Household visits to improve exclusive breastfeeding in Manyara'],
            S::CONCEPT_REVISION => ['Community score cards to improve accountability for maternal health in Pwani', 'Integrated outreach to improve trachoma surgery uptake in Dodoma'],
            S::CONCEPT_DECLINED => ['Solar lighting to improve night deliveries in rural dispensaries', 'Mobile money incentives to improve clinic attendance in Tabora', 'Drone delivery of blood products to remote hospitals'],
            S::SHORTLISTED => ['Triage training to reduce waiting times in paediatric outpatients', 'Community case management to improve malaria treatment in Kilombero', 'Same-day ART initiation to improve retention in Temeke', 'Male partner invitations to improve antenatal HIV testing in Chunya'],
            S::PROPOSAL_SUBMITTED => ['Electronic reminders to improve immunisation timeliness in Meru', 'Task-sharing to improve hypertension care in Kibaha'],
            S::PROPOSAL_REVIEW => ['Adolescent-friendly services to improve HIV retention in Mufindi', 'Community screening to find child malnutrition early in Bahi', 'Peer navigators to improve TB treatment completion in Ilemela'],
            S::PROPOSAL_REVISION => ['Quality improvement teams to reduce stillbirths in Kasulu'],
            S::NOT_RECOMMENDED => ['Mass media to increase hepatitis B testing in Tanga city', 'Vouchers to improve facility delivery in Liwale'],
            S::RECOMMENDED => ['Pharmacy refill clubs to improve diabetes control in Kinondoni', 'Community health workers to improve postnatal home visits in Newala'],
            S::WITH_DRCP => ['Facility audits to improve oxygen availability in regional hospitals'],
            S::WITH_DG => ['Village leaders to improve malaria testing before treatment in Muleba'],
        ]);
        $this->crowd($briefs, 11, [
            S::SUBMITTED => ['Brief: community health worker remuneration options for councils', 'Brief: scaling school deworming through local government', 'Brief: oxygen supply planning for regional hospitals'],
            S::SCREENED => ['Brief: hypertension medicines in the essential medicines list'],
            S::CONCEPT_REVIEW => ['Brief: integrating mental health into primary care guidelines', 'Brief: user fees and maternal care in faith-based facilities'],
            S::CONCEPT_DECLINED => ['Brief: a national strategy for snakebite care'],
            S::SHORTLISTED => ['Brief: malaria vaccine introduction in high-burden councils', 'Brief: council budgets for community health workers'],
            S::PROPOSAL_REVIEW => ['Brief: cervical screening targets in district health plans'],
            S::NOT_RECOMMENDED => ['Brief: tobacco tax and health spending'],
            S::RECOMMENDED => ['Brief: TB case finding in mining communities', 'Brief: newborn sickle cell screening in regional hospitals'],
            S::WITH_DG => ['Brief: pharmacy regulation for antibiotic dispensing'],
            S::APPROVED => ['Brief: referral transport for obstetric emergencies'],
        ]);
        $this->crowd($lab, 6, [
            S::AWARDED => ['Contamination checks for TB culture in regional laboratories', 'Portable centrifuges for haemoglobin testing in dispensaries'],
            S::AGREEMENT_ACCEPTED => ['Barcode tracking for laboratory samples in district hospitals'],
            S::APPROVED => ['Low-cost incubators for blood culture in district laboratories'],
            S::NOT_RECOMMENDED => ['A paper test for urinary schistosomiasis', 'Smartphone microscopy for soil-transmitted helminths', 'Reusable sample containers for viral load testing'],
            S::CONCEPT_DECLINED => ['Computer reading of chest X-rays in district hospitals', 'A rapid test for brucellosis in livestock keepers', 'Solar freezers for laboratory reagent storage', 'Microfluidic CD4 counting for health centres'],
            S::INELIGIBLE => ['Automated malaria slide staining for health centres'],
        ]);

        // The fund has run its course: close it, with last year's dates.
        $lab->update(['status' => 'closed', 'concept_deadline' => now()->subYear()->toDateString(), 'proposal_deadline' => now()->subMonths(9)->toDateString()]);

        $this->command?->info('Demo data added: 3 calls, ' . SeedApplication::count() . ' applications. Demo sign-ins use @demo.nimr.or.tz with the password "demo-password".');
    }

    // ── The three calls ─────────────────────────────────────────────────────

    private function calls(): array
    {
        $seed = SeedCall::updateOrCreate(['code' => '2627'], [
            'title' => 'Early-Career Researchers’ Implementation Science Seed Grant', 'fiscal_year' => '2026/27',
            'summary' => 'Focused, time-bound studies on how proven health interventions, services, policies or technologies can be delivered more effectively, equitably and sustainably in real Tanzanian settings.',
            'min_amount' => 2_000_000, 'max_amount' => 10_000_000, 'envelope' => 50_000_000, 'duration_months' => 12, 'equipment_cap_percent' => 20,
            'status' => 'open', 'concept_deadline' => now()->addWeeks(3)->toDateString(), 'proposal_deadline' => now()->addWeeks(10)->toDateString(),
            'config' => CallForm::defaults(),
        ]);

        $c = CallForm::defaults();
        foreach (['innovation', 'ip', 'workplan'] as $q) {
            $c['questions']['concept'][$q]['on'] = false;
        }
        $c['questions']['concept']['problem']['heading'] = '1. The policy or practice question';
        $c['questions']['concept']['problem']['limit'] = 300;
        foreach (['framework', 'mentorship'] as $q) {
            $c['questions']['proposal'][$q]['on'] = false;
        }
        $c['mentor']['on'] = false;
        $c['service_limit']['on'] = false;
        $c['prior_grant']['on'] = false;
        $c['equipment_cap']['on'] = false;
        $c['documents'] = ['mentor_letter' => 'off', 'cv' => 'required', 'support_letter' => 'optional'];
        $c['criteria']['development']['on'] = false;
        $c['criteria']['innovation']['on'] = false;
        $c['criteria']['science']['min'] = null;
        $c['pass_mark'] = 60;
        $c['page'] = [
            'about' => "Short grants for NIMR staff to turn finished research into a policy or practice brief that a ministry, council or programme can act on.\n\nThe grant pays for stakeholder consultation, writing and a dissemination meeting. It does not fund new data collection.",
            'who' => 'Any NIMR scientist, permanent or contract, with completed research findings to share',
            'priorities_intro' => 'Briefs on the national priorities below are favoured.',
            'priorities' => ['Primary health care', 'HIV, TB and malaria', 'Maternal and child health', 'Health financing and Bima ya Afya'],
        ];
        $briefs = SeedCall::updateOrCreate(['code' => '2627PB'], [
            'title' => 'Policy and Practice Brief Grant', 'fiscal_year' => '2026/27',
            'summary' => 'Up to TZS 5 million to turn completed research into a brief that decision-makers can act on.',
            'min_amount' => 1_000_000, 'max_amount' => 5_000_000, 'envelope' => 20_000_000, 'duration_months' => 6, 'equipment_cap_percent' => 0,
            'status' => 'open', 'concept_deadline' => now()->addWeeks(5)->toDateString(), 'proposal_deadline' => now()->addWeeks(12)->toDateString(),
            'config' => $c,
        ]);

        $l = CallForm::defaults();
        $l['service_limit']['years'] = 8;
        $l['criteria']['relevance']['max'] = 15;
        $l['criteria']['impact']['max'] = 10;
        $l['criteria']['innovation']['max'] = 20;
        $l['page']['about'] = "A fund for laboratory teams to develop and validate low-cost diagnostics and laboratory tools for Tanzanian health facilities.\n\nProjects must include a validation step and a plan for adoption by facilities or industry.";
        $l['page']['who'] = 'NIMR laboratory scientists and health laboratory officers, permanent or contract';
        $l['page']['priorities'] = ['Affordable diagnostics', 'Sample transport and cold chain', 'Digital laboratory tools', 'Quality assurance'];
        $lab = SeedCall::updateOrCreate(['code' => '2526LI'], [
            'title' => 'Laboratory Innovation Fund', 'fiscal_year' => '2025/26',
            'summary' => 'Last year’s fund for low-cost diagnostics and laboratory tools.',
            'min_amount' => 3_000_000, 'max_amount' => 15_000_000, 'envelope' => 70_000_000, 'duration_months' => 12, 'equipment_cap_percent' => 40,
            // Open while the stories run; closed at the end.
            'status' => 'open', 'concept_deadline' => now()->addWeeks(1)->toDateString(), 'proposal_deadline' => now()->addWeeks(2)->toDateString(),
            'config' => $l,
        ]);

        return [$seed, $briefs, $lab];
    }

    // ── One application, moved through the real workflow to $target ────────

    /**
     * Each application happens a few days ago (1 to 11 days, fixed by its title),
     * so lists have a spread of dates and review rounds are still within their 14 days.
     */
    private function story(SeedCall $call, User $pi, string $title, string $target): void
    {
        $seed = crc32($title);
        Carbon::setTestNow(now()->subDays(1 + $seed % 11)->setTime(8 + $seed % 9, $seed % 60));
        try {
            $this->tell($call, $pi, $title, $target);
        } finally {
            Carbon::setTestNow();
        }
    }

    /** Many applications at once: [status => [titles]], each from a new applicant at the call's centres. */
    private function crowd(SeedCall $call, int $years, array $byStatus): void
    {
        static $i = 0;
        $firsts = ['Amina', 'Hamisi', 'Rose', 'Yusuf', 'Esther', 'Faraji', 'Upendo', 'Juma', 'Mary', 'Salim', 'Happiness', 'Godfrey', 'Tumaini', 'Abdallah', 'Leah', 'Erasto', 'Winfrida', 'Khamis', 'Janeth', 'Seif', 'Anna', 'Rashidi', 'Glory', 'Nassoro', 'Beatrice', 'Frank', 'Zaituni', 'Gabriel', 'Mwajuma', 'Elisha'];
        $lasts  = ['Mwakalebela', 'Mrisho', 'Kisanga', 'Swai', 'Mmbaga', 'Kileo', 'Mfinanga', 'Komba', 'Chuwa', 'Mwamba', 'Lukumay', 'Minja', 'Mbwambo', 'Lyakurwa', 'Urassa', 'Kahindi', 'Mapunda', 'Nkya', 'Haule', 'Mwanga', 'Sanga', 'Kapinga', 'Tarimo', 'Mtui'];
        $centres = ['Muhimbili Centre', 'Mwanza Centre', 'Tanga Centre', 'Amani Centre', 'Mbeya Medical Research Centre', 'Dodoma Centre', 'Tukuyu Centre', 'Tabora Centre', 'Ngongongare Centre', 'Headquarters'];

        foreach ($byStatus as $target => $titles) {
            foreach ($titles as $title) {
                [$first, $last] = [$firsts[$i % count($firsts)], $lasts[$i % count($lasts)]];
                $handle = Str::lower($first . '.' . $last);
                $pi = $this->person("Dr {$first} {$last}", $handle, 'pi', $centres[$i % count($centres)], 1 + ($i + $years) % $years);
                $this->story($call, $pi, $title, (string) $target);
                $i++;
            }
        }
    }

    private function tell(SeedCall $call, User $pi, string $title, string $target): void
    {
        $w = $this->workflow;
        $app = $w->start($pi, $call);
        $this->writeConcept($app, $title);
        if ($target === S::DRAFT) {
            return;
        }

        $w->submitConcept($app->fresh());
        if ($target === S::SUBMITTED) {
            return;
        }
        if ($target === 'returned') {
            $w->screen($app->fresh(), $this->gmu, [], 'return', 'Please name your mentor’s NIMR centre and add the district hospital as a collaborator.');

            return;
        }
        if ($target === S::INELIGIBLE) {
            $w->screen($app->fresh(), $this->gmu, [], 'ineligible', 'GMU records show you led a research grant above USD 10,000 as PI in 2024, so this call is not open to you as lead applicant.');

            return;
        }

        $w->screen($app->fresh(), $this->gmu, [], 'eligible', null);
        if ($target === S::SCREENED) {
            return;
        }

        $this->openRound($app);
        if ($target === S::CONCEPT_REVIEW) {
            $this->reviewPartly($app, null);

            return;
        }
        $this->reviewAll($app, 2, $target === S::CONCEPT_DECLINED ? ['reject', 'revise'] : ['accept', 'revise']);
        if ($target === S::CONCEPT_REVISION) {
            $w->decide($app->fresh(), $this->gmu, 'revise', 'Please describe the referral pathway, and narrow the outcomes to adoption and fidelity.');

            return;
        }
        if ($target === S::CONCEPT_DECLINED) {
            $w->decide($app->fresh(), $this->gmu, 'decline', 'The panel found the implementation question too broad for this fund.');

            return;
        }

        $w->decide($app->fresh(), $this->gmu, 'advance', null);
        if ($target === S::SHORTLISTED) {
            return;
        }

        $this->writeProposal($app->fresh());
        $w->submitProposal($app->fresh());
        if ($target === S::PROPOSAL_SUBMITTED) {
            return;
        }

        $this->openRound($app);
        if ($target === S::PROPOSAL_REVIEW) {
            $this->reviewPartly($app, 'good');

            return;
        }
        $low = $target === S::NOT_RECOMMENDED;
        $this->reviewAll($app, 2, $low ? ['reject', 'revise'] : ['accept', 'accept'], $low ? 'low' : 'good');
        if ($target === S::PROPOSAL_REVISION) {
            $w->decide($app->fresh(), $this->gmu, 'revise', 'Strengthen the validation design and justify the equipment line.');

            return;
        }
        if ($low) {
            $w->decide($app->fresh(), $this->gmu, 'decline', 'Below the funding threshold on scientific quality.');

            return;
        }

        $w->decide($app->fresh(), $this->gmu, 'advance', null);
        if ($target === S::RECOMMENDED) {
            return;
        }

        // Approval follows the call's own chain: GMU, then DRCP and the DG where switched on.
        $w->approve($app->fresh(), $this->gmu, 'gmu', 'approved', null);
        if ($app->fresh()->status === S::WITH_DRCP) {
            if ($target === S::WITH_DRCP) {
                return;
            }
            $w->approve($app->fresh(), $this->drcp, 'drcp', 'approved', null);
        }
        if ($app->fresh()->status === S::WITH_DG) {
            if ($target === S::WITH_DG) {
                return;
            }
            $w->approve($app->fresh(), $this->dg, 'dg', $target === S::AWARDED ? 'conditions' : 'approved',
                $target === S::AWARDED ? 'Ethics approval must be in place before the first disbursement.' : null);
        }
        if ($target === S::APPROVED) {
            return;
        }

        $w->issueAgreement($app->fresh());
        if ($target === S::AGREEMENT_ISSUED) {
            return;
        }
        $w->acceptAgreement($app->fresh());
        if ($target === S::AGREEMENT_ACCEPTED) {
            return;
        }

        $path = 'seed-grant/' . $app->reference . '/' . Str::random(12) . '.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n% demo signed agreement\n");
        $w->recordSignedAgreement($app->fresh(), $path, 'Signed grant agreement.pdf');
    }

    private function openRound(SeedApplication $app): void
    {
        // Two of the four reviewers, in turn, so each has a realistic share.
        $pairs = [[0, 1], [0, 2], [1, 3], [0, 3], [1, 2], [2, 3]];
        foreach ($pairs[crc32($app->reference) % count($pairs)] as $r) {
            $reviewer = $this->reviewers[$r];
            $assignment = $this->workflow->assignReviewer($app->fresh(), $reviewer, $this->gmu);
            $this->workflow->declareInterest($assignment, false, null);
        }
        $this->workflow->startReview($app->fresh());
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function person(string $name, string $handle, string $role, string $centre, int $years): User
    {
        $user = User::firstOrNew(['email' => "{$handle}@demo.nimr.or.tz"]);
        $user->fill([
            'name' => $name, 'password' => 'demo-password', 'is_active' => true,
            'role_id' => Role::where('name', $role)->value('id'),
            'research_center' => $centre, 'department' => 'Health Systems Research',
            'designation' => $role === 'pi' ? 'Research Scientist II' : 'Senior Research Scientist',
            'phone' => '+255 7' . random_int(10, 89) . ' ' . random_int(100, 999) . ' ' . random_int(100, 999),
            'employment_type' => $years > 5 ? 'permanent' : 'contract', 'first_appointment_date' => now()->subYears($years)->subMonths(4),
        ]);
        $user->save();

        return $user;
    }

    private function text(int $words, string $topic): string
    {
        $sentences = [
            "This study examines how {$topic} can be delivered reliably in routine primary care.",
            'We will work with facility in-charges and district teams from the start.',
            'Implementation outcomes are adoption, fidelity and reach, measured monthly.',
            'Findings will be shared with the council health management team and the programme.',
        ];
        $out = [];
        while (str_word_count(implode(' ', $out)) < $words) {
            $out[] = $sentences[count($out) % count($sentences)];
        }

        return implode(' ', $out);
    }

    private function writeConcept(SeedApplication $application, string $title): void
    {
        $form = $application->call->form();
        $topic = Str::lower(Str::after(Str::before($title, ' to '), 'Brief: ')) ?: 'the intervention';
        $concept = [];
        foreach ($form->sections('concept') as $key => [, , $limit]) {
            $concept[$key] = $this->text((int) ($limit * 0.6), $topic);
        }
        foreach (array_keys($form->choices()) as $key) {
            $concept[$key . '_answer'] = $key === 'innovation' ? 'yes' : 'no';
        }
        if (isset($concept['ip'])) {
            $concept['ip'] = '';
        }
        $concept['prior_grant'] = 'no';
        $concept['co_investigators'] = 'Dr Halima Said, Medical Officer, district hospital; Mr Ally Juma, Data Manager, NIMR.';
        $concept['declarations'] = array_keys($form->declarations());

        $application->fill([
            'title' => $title, 'concept' => $concept, 'declared_no_prior_large_grant' => true,
            'mentor_name' => 'Dr Joseph Kweka', 'mentor_designation' => 'Chief Research Scientist',
            'mentor_centre' => $application->pi->research_center, 'mentor_suitability' => 'Has led implementation studies in the same facilities and supervises two early-career scientists.',
        ])->save();

        // A budget inside this call's award range.
        $call = $application->call;
        $total = (int) round(($call->min_amount + ($call->max_amount - $call->min_amount) * (0.35 + 0.4 * (crc32($title) % 100) / 100)) / 50_000) * 50_000;
        $this->workflow->saveBudget($application, 'concept', [
            ['item' => 'Research assistant allowances', 'amount' => (int) ($total * 0.5)],
            ['item' => 'Travel to facilities', 'amount' => (int) ($total * 0.25)],
            ['item' => 'Stakeholder meetings', 'amount' => (int) ($total * 0.15)],
            ['item' => 'Dissemination', 'amount' => $total - (int) ($total * 0.5) - (int) ($total * 0.25) - (int) ($total * 0.15)],
        ]);
    }

    private function writeProposal(SeedApplication $application): void
    {
        $form = $application->call->form();
        $proposal = [];
        foreach ($form->sections('proposal') as $key => [, , $limit]) {
            $proposal[$key] = $this->text((int) ($limit * 0.5), Str::lower(Str::after($application->title, 'Brief: ')));
        }
        $application->forceFill(['proposal' => $proposal])->save();

        // A quarterly budget inside the range, with equipment under the call's cap.
        $total = $application->budgetTotal('concept');
        $cap = $form->equipmentCap();
        $equipment = $cap ? (int) ($total * min(0.15, $cap / 100 * 0.8)) : 0;
        $rest = $total - $equipment;
        $q = fn ($share) => (int) floor($rest * $share / 4);
        $lines = [
            ['category' => 'personnel', 'item' => 'Research assistants', 'q1' => $q(0.45), 'q2' => $q(0.45), 'q3' => $q(0.45), 'q4' => $q(0.45)],
            ['category' => 'research', 'item' => 'Travel and data collection', 'q1' => $q(0.4), 'q2' => $q(0.4), 'q3' => $q(0.4), 'q4' => $q(0.4)],
            ['category' => 'indirect', 'item' => 'Institutional overheads', 'q1' => $q(0.15), 'q2' => $q(0.15), 'q3' => $q(0.15), 'q4' => $q(0.15)],
        ];
        if ($equipment) {
            $lines[] = ['category' => 'equipment', 'item' => 'Field equipment', 'q1' => $equipment];
        }
        $this->workflow->saveBudget($application, 'proposal', $lines);

        $files = ['mentor_letter' => 'Mentor commitment letter.pdf', 'cv' => 'Applicant CV.pdf', 'support_letter' => 'Council support letter.pdf'];
        foreach ($form->documents() as $kind => [$label, $required]) {
            $path = 'seed-grant/' . $application->reference . '/' . Str::random(12) . '.pdf';
            Storage::disk('local')->put($path, "%PDF-1.4\n% demo placeholder\n");
            $application->documents()->create(['kind' => $kind, 'file_path' => $path, 'file_name' => $files[$kind], 'file_size' => 32]);
        }
    }

    /** @param  string|null  $scores  'good' or 'low' for the full proposal, null for a concept note */
    private function reviewAll(SeedApplication $application, int $howMany, array $recommendations, ?string $scores = null, ?array $plan = null): void
    {
        $application->refresh()->load('assignments.application');
        $criteria = $application->call->form()->criteria();
        $comments = [
            'The implementation question is clear and the facilities are committed. The outcomes are well chosen.',
            'Name an implementation framework and narrow the outcomes to adoption and fidelity for a 12-month study.',
        ];
        foreach ($application->activeAssignments()->values() as $i => $assignment) {
            // By default the first $howMany submit; $plan picks who submits (true) or saves a draft (false).
            $submit = $plan === null ? ($i < $howMany ? true : null) : ($plan[$i] ?? null);
            if ($submit === null) {
                continue;
            }
            $marks = [];
            if ($scores) {
                // A share of each criterion's maximum: strong, or weak on science.
                // Varied by title, so the ranking has a spread; 'good' always clears the pass mark.
                $spread = crc32($application->title) % 12 / 100;
                foreach ($criteria as $key => [, , $max]) {
                    $share = $scores === 'good' ? 0.8 + $spread - $i * 0.03 : ($key === 'science' ? 0.4 : 0.52 + $spread);
                    $marks[$key] = round($max * $share * 2) / 2;
                }
            }
            $this->workflow->saveReview($assignment->fresh(), [
                'recommendation' => $recommendations[$i] ?? 'accept',
                'comments'       => $comments[$i] ?? $comments[0],
                'confidential_note' => $i === 1 ? 'The budget looks tight for the travel planned.' : null,
                'scores'         => $marks,
            ], $submit);
        }
    }

    /**
     * A round still under way: neither reviewer has submitted, one has, the other has,
     * or one has and the other has a draft (fixed by the title), so every reviewer has work waiting.
     */
    private function reviewPartly(SeedApplication $application, ?string $scores): void
    {
        $plan = [[], [0 => true], [1 => true], [0 => true, 1 => false]][crc32($application->title) % 4];
        $this->reviewAll($application, 0, ['accept', 'accept'], $scores, $plan);
    }
}
