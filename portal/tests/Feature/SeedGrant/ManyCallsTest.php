<?php

namespace Tests\Feature\SeedGrant;

use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\User;
use App\Support\CallForm;
use App\Support\SeedGrant as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Many calls, each with its own switched-on features and settings, and the
 * settings locking once a call opens.
 */
class ManyCallsTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    private User $gmu;
    private User $pi;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gmu.external_grants' => false]);

        $this->gmu = $this->user('coordinator');
        $this->pi = $this->user('pi', ['employment_type' => 'contract', 'first_appointment_date' => now()->subYears(2)]);
        $this->user('drcp');
        $this->user('dg');
    }

    /** The settings form's payload for a config, as the page would post it. */
    private function features(array $config): array
    {
        $flag = fn ($v) => $v ? '1' : '0';
        $out = ['title_words' => $config['title_words'], 'pass_mark' => $config['pass_mark'], 'documents' => $config['documents']];
        foreach (['concept', 'proposal'] as $stage) {
            foreach ($config['questions'][$stage] as $key => $q) {
                $out['questions'][$stage][$key] = ['on' => $flag($q['on']), 'limit' => $q['limit'], 'heading' => $q['heading'], 'question' => $q['question']];
            }
        }
        foreach (['mentor', 'co_investigators', 'prior_grant', 'declarations', 'service_limit', 'equipment_cap'] as $f) {
            $out[$f] = ['on' => $flag($config[$f]['on'])] + array_diff_key($config[$f], ['on' => 1]);
        }
        foreach ($config['criteria'] as $key => $c) {
            $out['criteria'][$key] = ['on' => $flag($c['on']), 'max' => $c['max'], 'min' => $c['min']];
        }
        $out['approval'] = array_map($flag, $config['approval']);

        return $out;
    }

    private function callPayload(array $overrides = [], ?array $config = null): array
    {
        return array_replace([
            'title' => 'Seed grant', 'code' => '2627', 'fiscal_year' => '2026/27', 'min_amount' => 2_000_000,
            'max_amount' => 10_000_000, 'duration_months' => 12, 'equipment_cap_percent' => 20, 'status' => 'open',
            'concept_deadline' => now()->addMonth()->toDateString(),
            'features' => $this->features($config ?? CallForm::defaults()),
        ], $overrides);
    }

    public function test_gmu_runs_several_calls_each_with_its_own_settings(): void
    {
        // First call: the seed grant as it is.
        $this->actingAs($this->gmu)->post(route('seed.calls.store'), $this->callPayload())->assertSessionHasNoErrors();
        $first = SeedCall::where('code', '2627')->firstOrFail();

        // A new call starts from the last one's settings.
        $this->actingAs($this->gmu)->get(route('seed.calls.create'))->assertOk()
            ->assertSee('Started from the settings of')->assertSee('Research problem and justification');

        // Second call: no innovation or IP questions, a longer problem section, no years limit.
        // A request to skip the DRCP is ignored: every call goes GMU, DRCP, DG.
        $config = CallForm::defaults();
        $config['questions']['concept']['innovation']['on'] = false;
        $config['questions']['concept']['ip']['on'] = false;
        $config['questions']['concept']['problem']['limit'] = 400;
        $config['approval']['drcp'] = false;
        $config['service_limit']['on'] = false;
        $this->actingAs($this->gmu)->post(route('seed.calls.store'), $this->callPayload(['title' => 'Policy brief grant', 'code' => '2627PB'], $config))
            ->assertSessionHasNoErrors();
        $second = SeedCall::where('code', '2627PB')->firstOrFail();

        $form = $second->form();
        $this->assertArrayNotHasKey('innovation', $form->sections('concept'));
        $this->assertSame(400, $form->sections('concept')['problem'][2]);
        $this->assertSame(['gmu', 'drcp', 'dg'], $form->approvalLevels());
        $this->assertNull($form->serviceYears());
        // The first call is untouched.
        $this->assertArrayHasKey('innovation', $first->form()->sections('concept'));
        $this->assertSame(['gmu', 'drcp', 'dg'], $first->form()->approvalLevels());

        // Staff see both calls, and apply to each separately.
        $this->actingAs($this->pi)->get(route('seed.calls.index'))->assertOk()->assertSee('Seed grant')->assertSee('Policy brief grant');
        $this->actingAs($this->pi)->post(route('seed.calls.apply', $first))->assertRedirect();
        $this->actingAs($this->pi)->post(route('seed.calls.apply', $second))->assertRedirect();
        $this->assertSame(2, SeedApplication::where('pi_id', $this->pi->id)->count());
        $this->assertSame('SG-2627PB-001', SeedApplication::where('seed_call_id', $second->id)->value('reference'));

        // The second call's form asks only what it switched on.
        $application = SeedApplication::where('seed_call_id', $second->id)->firstOrFail();
        $this->actingAs($this->pi)->get(route('seed.applications.edit', $application))->assertOk()
            ->assertSee('/ 400 words', false)->assertDontSee('7. Innovation potential');

        // My applications lists both.
        $this->actingAs($this->pi)->get(route('seed.mine'))->assertOk()->assertSee('SG-2627-001')->assertSee('SG-2627PB-001');
    }

    public function test_every_call_goes_to_the_drcp_after_gmu_even_if_an_old_setting_skipped_it(): void
    {
        $config = CallForm::defaults();
        $config['approval']['drcp'] = false;
        $call = SeedCall::create(['title' => 'Old setting', 'code' => '9001', 'fiscal_year' => '2026/27', 'min_amount' => 1, 'max_amount' => 10,
            'status' => 'open', 'config' => $config]);
        $application = new SeedApplication(['title' => 'X']);
        $application->forceFill(['seed_call_id' => $call->id, 'pi_id' => $this->pi->id, 'reference' => 'SG-9001-001', 'status' => S::RECOMMENDED])->save();

        app(\App\Services\SeedGrant\Workflow::class)->approve($application, $this->gmu, 'gmu', 'approved', null);
        $this->assertSame(S::WITH_DRCP, $application->fresh()->status);

        // The DG cannot decide before the DRCP has cleared it.
        $dg = User::whereHas('role', fn ($q) => $q->where('name', 'dg'))->first();
        $this->actingAs($dg)->post(route('seed.approvals.decide', $application), ['decision' => 'approved'])->assertSessionHasErrors();
        $this->assertSame(S::WITH_DRCP, $application->fresh()->status);
    }

    public function test_once_open_a_call_s_form_is_locked_except_raising_word_limits(): void
    {
        $this->actingAs($this->gmu)->post(route('seed.calls.store'), $this->callPayload())->assertSessionHasNoErrors();
        $call = SeedCall::firstOrFail();

        $config = $call->form()->config();
        $config['questions']['concept']['problem']['on'] = false;     // ignored: locked
        $config['questions']['concept']['problem']['limit'] = 100;    // ignored: lowering
        $config['questions']['concept']['objectives']['limit'] = 300; // allowed: raising
        $config['approval']['dg'] = false;                            // ignored: the chain is fixed

        $this->actingAs($this->gmu)->put(route('seed.calls.update', $call), $this->callPayload(['max_amount' => 12_000_000], $config))
            ->assertSessionHasNoErrors();

        $form = $call->fresh()->form();
        $this->assertArrayHasKey('problem', $form->sections('concept'));
        $this->assertSame(250, $form->sections('concept')['problem'][2]);
        $this->assertSame(300, $form->sections('concept')['objectives'][2]);
        $this->assertSame(['gmu', 'drcp', 'dg'], $form->approvalLevels());
        $this->assertSame(10_000_000, $call->fresh()->max_amount, 'Amounts stay as applicants found them.');

        $this->actingAs($this->gmu)->get(route('seed.calls.edit', $call))->assertOk()->assertSee('so its form is locked');
    }

    /** Two open calls, each with one submitted concept note. */
    private function twoCallsWithSubmissions(): array
    {
        $calls = [];
        foreach ([['Seed grant', '2627'], ['Policy brief grant', '2627PB']] as $i => [$title, $code]) {
            $call = SeedCall::create(['title' => $title, 'code' => $code, 'fiscal_year' => '2026/27', 'min_amount' => 1, 'max_amount' => 10, 'status' => 'open', 'envelope' => 50_000_000]);
            $pi = $this->user('pi', ['name' => "Applicant {$code}", 'research_center' => $i ? 'Tanga Centre' : 'Mwanza Centre']);
            $application = new SeedApplication(['title' => "Study in {$code}"]);
            $application->forceFill(['seed_call_id' => $call->id, 'pi_id' => $pi->id, 'reference' => "SG-{$code}-001", 'status' => S::SUBMITTED])->save();
            $calls[] = $call;
        }

        return $calls;
    }

    public function test_gmu_sees_submissions_of_every_call_grouped_and_can_export_them(): void
    {
        [$seed, $policy] = $this->twoCallsWithSubmissions();

        $this->actingAs($this->gmu)->get(route('seed.submissions.index', ['call' => 'all']))->assertOk()
            ->assertSee('All calls')->assertSee('SG-2627-001')->assertSee('SG-2627PB-001')
            ->assertSee('Policy brief grant')->assertSee('Only this call');
        $this->actingAs($this->gmu)->get(route('seed.submissions.index', ['call' => $seed->id]))->assertOk()
            ->assertSee('SG-2627-001')->assertDontSee('SG-2627PB-001');

        $csv = $this->actingAs($this->gmu)->get(route('seed.submissions.export', ['call' => 'all']))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Call code",Call,Reference', $csv);
        $this->assertStringContainsString('2627PB,"Policy brief grant",SG-2627PB-001', $csv);
        $this->assertStringContainsString('SG-2627-001', $csv);

        $this->actingAs($this->pi)->get(route('seed.submissions.export', ['call' => 'all']))->assertForbidden();
    }

    public function test_the_gmu_dashboard_has_a_card_per_call_and_one_action_list(): void
    {
        $this->twoCallsWithSubmissions();

        $this->actingAs($this->gmu)->get(route('dashboard'))->assertOk()
            ->assertSee('2 calls running')->assertSee('Needs GMU')->assertSee('Needs your action')
            ->assertSee('Seed grant')->assertSee('Policy brief grant')
            ->assertSee('SG-2627PB-001')->assertSee('Screen');
    }

    public function test_the_call_page_follows_the_call_and_gmu_sees_its_figures(): void
    {
        $config = CallForm::defaults();
        $config['service_limit']['on'] = false;
        $config['page']['priorities'] = [];
        $config['page']['about'] = "First paragraph of this call.\n\nSecond paragraph.";
        $call = SeedCall::create(['title' => 'Open to all staff', 'code' => '2700', 'fiscal_year' => '2027/28', 'min_amount' => 1, 'max_amount' => 10,
            'status' => 'open', 'config' => $config]);

        $this->actingAs($this->pi)->get(route('seed.calls.show', $call))->assertOk()
            ->assertSee('First paragraph of this call.')->assertSee('Second paragraph.')
            ->assertDontSee('years’ service at NIMR', false)->assertDontSee('Priority areas')
            ->assertDontSee('Where this call stands');

        $this->actingAs($this->gmu)->get(route('seed.calls.show', $call))->assertOk()->assertSee('Where this call stands');
    }

    public function test_drafts_are_hidden_from_staff(): void
    {
        $this->actingAs($this->gmu)->post(route('seed.calls.store'), $this->callPayload(['status' => 'draft', 'title' => 'Hidden call']))->assertSessionHasNoErrors();
        $call = SeedCall::firstOrFail();

        $this->actingAs($this->pi)->get(route('seed.calls.index'))->assertOk()->assertDontSee('Hidden call');
        $this->actingAs($this->pi)->get(route('seed.calls.show', $call))->assertNotFound();
        $this->actingAs($this->pi)->post(route('seed.calls.apply', $call))->assertNotFound();
        $this->actingAs($this->gmu)->get(route('seed.calls.index'))->assertOk()->assertSee('Hidden call');
    }

    public function test_a_question_must_remain_and_the_pass_mark_must_fit_the_criteria(): void
    {
        $config = CallForm::defaults();
        foreach ($config['questions']['proposal'] as $key => $q) {
            $config['questions']['proposal'][$key]['on'] = false;
        }
        $config['pass_mark'] = 150;

        $this->actingAs($this->gmu)->post(route('seed.calls.store'), $this->callPayload(['status' => 'draft'], $config))
            ->assertSessionHasErrors(['features.questions.proposal', 'features.pass_mark']);
        $this->assertSame(0, SeedCall::count());
    }
}
