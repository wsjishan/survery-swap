<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$errors = pull_validation_errors();
$categories = survey_categories();
$rewardTiers = survey_reward_tiers();
$oldQuestions = $_SESSION['_old']['questions'] ?? [''];
$oldQuestionTypes = $_SESSION['_old']['question_types'] ?? [];
$oldQuestionOptions = $_SESSION['_old']['question_options'] ?? [];

if (!is_array($oldQuestions) || $oldQuestions === []) {
  $oldQuestions = [''];
}

$selectedReward = old('reward_points', (string) SURVEY_DEFAULT_REWARD_POINTS);

$rewardPoints = ctype_digit($selectedReward) ? (int) $selectedReward : SURVEY_DEFAULT_REWARD_POINTS;

if (!is_valid_reward_tier($rewardPoints)) {
    $rewardPoints = SURVEY_DEFAULT_REWARD_POINTS;
}

$listingFee = survey_listing_fee();
$rewardPool = calculate_survey_reward_pool_points($rewardPoints);
$totalRequired = calculate_survey_total_cost($rewardPoints);
$balance = (int) $user['points'];
$balanceAfterPublish = $balance - $totalRequired;
$canSubmit = $balanceAfterPublish >= 0;
$questionCount = count($oldQuestions);

$pageTitle = 'Submit Survey';
require_once __DIR__ . '/templates/header.php';
?>
<section class="section page-head">
  <h1 class="page-title">Submit a New Survey</h1>
  <p class="page-subtitle">Build a native in-app survey, set reward per completion, and publish when your budget is ready.</p>
</section>

<section class="section card card-pad submit-shell">
  <div class="submit-banner">
    <p class="submit-banner-title">Native Survey Builder</p>
    <p class="submit-banner-copy">All responses are collected directly inside SurveySwap. You can use short-text and multiple-choice questions.</p>
  </div>

  <form action="<?= e(url('/actions/submit_survey_action.php')) ?>" method="post" novalidate class="form-grid submit-form">
    <?= csrf_field() ?>

    <div class="submit-layout">
      <div class="submit-main form-grid">
        <div class="field">
          <label class="field-label" for="title">Survey Title</label>
          <input id="title" name="title" type="text" class="input <?= isset($errors['title']) ? 'is-invalid' : '' ?>" value="<?= e(old('title')) ?>" required>
          <?php if (isset($errors['title'])): ?><p class="error-text"><?= e($errors['title']) ?></p><?php endif; ?>
        </div>

        <div class="field">
          <label class="field-label" for="description">Description (optional)</label>
          <textarea id="description" name="description" class="textarea <?= isset($errors['description']) ? 'is-invalid' : '' ?>" placeholder="What is this survey about, and who should answer it?"><?= e(old('description')) ?></textarea>
          <?php if (isset($errors['description'])): ?><p class="error-text"><?= e($errors['description']) ?></p><?php endif; ?>
        </div>

        <div class="field">
          <div class="submit-question-head">
            <label class="field-label">Survey Questions</label>
            <p class="submit-question-meta"><span id="question-count"><?= e((string) $questionCount) ?></span> / <?= e((string) native_survey_question_max()) ?> questions</p>
          </div>
          <p class="submit-helper">Keep questions clear and concise. Minimum <?= e((string) native_survey_question_min()) ?> question, maximum <?= e((string) native_survey_question_max()) ?>.</p>
          <div id="question-builder" class="form-grid">
            <?php foreach ($oldQuestions as $index => $questionText): ?>
              <?php
              $questionType = (string) ($oldQuestionTypes[$index] ?? native_question_type_short_text());
              if (!in_array($questionType, native_question_type_options(), true)) {
                  $questionType = native_question_type_short_text();
              }
              $questionOptions = (string) ($oldQuestionOptions[$index] ?? '');
              ?>
              <div class="field question-row">
                <label class="field-label" for="question_<?= e((string) $index) ?>">Question <?= e((string) ($index + 1)) ?></label>
                <div class="actions-row submit-question-controls">
                  <input id="question_<?= e((string) $index) ?>" name="questions[]" type="text" class="input <?= isset($errors['questions']) ? 'is-invalid' : '' ?>" value="<?= e((string) $questionText) ?>" maxlength="240" required>
                  <select name="question_types[]" class="select question-type">
                    <option value="<?= e(native_question_type_short_text()) ?>" <?= $questionType === native_question_type_short_text() ? 'selected' : '' ?>>Short text</option>
                    <option value="<?= e(native_question_type_multiple_choice()) ?>" <?= $questionType === native_question_type_multiple_choice() ? 'selected' : '' ?>>Multiple choice</option>
                  </select>
                  <button type="button" class="btn btn-ghost btn-small question-remove">Remove</button>
                </div>
                <div class="field question-options-wrap" <?= $questionType === native_question_type_multiple_choice() ? '' : 'hidden' ?>>
                  <label class="field-label">Options (one per line)</label>
                  <textarea name="question_options[]" class="textarea question-options" rows="4" placeholder="Option A&#10;Option B&#10;Option C"><?= e($questionOptions) ?></textarea>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="actions-row">
            <button type="button" id="question-add" class="btn btn-secondary btn-small">Add Question</button>
          </div>
          <?php if (isset($errors['questions'])): ?><p class="error-text"><?= e($errors['questions']) ?></p><?php endif; ?>
        </div>

        <div class="form-grid form-grid-2">
          <div class="field">
            <label class="field-label" for="target_audience">Target Audience</label>
            <input id="target_audience" name="target_audience" type="text" class="input <?= isset($errors['target_audience']) ? 'is-invalid' : '' ?>" value="<?= e(old('target_audience', 'General')) ?>" required>
            <?php if (isset($errors['target_audience'])): ?><p class="error-text"><?= e($errors['target_audience']) ?></p><?php endif; ?>
          </div>

          <div class="field">
            <label class="field-label" for="category">Category</label>
            <select id="category" name="category" class="select <?= isset($errors['category']) ? 'is-invalid' : '' ?>" required>
              <option value="">Select a category</option>
              <?php foreach ($categories as $item): ?>
                <option value="<?= e($item) ?>" <?= old('category') === $item ? 'selected' : '' ?>><?= e($item) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category'])): ?><p class="error-text"><?= e($errors['category']) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="form-grid form-grid-2">
          <div class="field">
            <label class="field-label" for="estimated_minutes">Estimated Minutes</label>
            <input id="estimated_minutes" name="estimated_minutes" type="number" min="1" max="120" class="input <?= isset($errors['estimated_minutes']) ? 'is-invalid' : '' ?>" value="<?= e(old('estimated_minutes')) ?>" placeholder="e.g. 8">
            <?php if (isset($errors['estimated_minutes'])): ?><p class="error-text"><?= e($errors['estimated_minutes']) ?></p><?php endif; ?>
          </div>

          <div class="field">
            <label class="field-label" for="reward_points">Reward / Completion</label>
            <select id="reward_points" name="reward_points" class="select <?= isset($errors['reward_points']) ? 'is-invalid' : '' ?>" required>
              <?php foreach ($rewardTiers as $tier): ?>
                <option value="<?= e((string) $tier) ?>" <?= $selectedReward === (string) $tier ? 'selected' : '' ?>><?= e((string) $tier) ?> point(s)</option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['reward_points'])): ?><p class="error-text"><?= e($errors['reward_points']) ?></p><?php endif; ?>
          </div>
        </div>
      </div>

      <aside class="submit-side">
        <div class="card card-pad submit-budget <?= $canSubmit ? 'is-affordable' : 'is-tight' ?>" id="budget-card">
          <div class="submit-budget-head">
            <h3 class="card-title">Budget Summary</h3>
            <span class="submit-budget-status" id="budget-status"><?= $canSubmit ? 'Ready to publish' : 'Insufficient balance' ?></span>
          </div>
          <p class="meta-item"><span class="meta-label">Reward per response:</span> <span id="budget-reward"><?= e((string) $rewardPoints) ?></span> point(s)</p>
          <p class="meta-item"><span class="meta-label">Publish cost:</span> <span id="budget-total"><?= e((string) $totalRequired) ?></span> points</p>
          <p class="meta-item"><span class="meta-label">Current balance:</span> <span id="budget-balance"><?= e((string) $balance) ?></span> points</p>
          <p class="meta-item"><span class="meta-label">Balance after publish:</span> <span id="budget-after"><?= e((string) $balanceAfterPublish) ?></span> points</p>
          <p class="meta-item submit-budget-warning" id="budget-warning"<?= $canSubmit ? ' hidden' : '' ?>>Adjust reward or add more points to continue.</p>

          <div class="form-actions submit-actions">
            <button type="submit" class="btn btn-primary" id="publish-submit" <?= $canSubmit ? '' : 'disabled' ?>>Submit Survey</button>
          </div>
        </div>
      </aside>
    </div>
    <div class="submit-footnote">
      Cost formula: reward x <?= e((string) SURVEY_REWARD_COST_MULTIPLIER) ?>
    </div>
  </form>

</section>
  <script>
  (function () {
    var rewardInput = document.getElementById('reward_points');
    var rewardEl = document.getElementById('budget-reward');
    var totalEl = document.getElementById('budget-total');
    var balanceEl = document.getElementById('budget-balance');
    var afterEl = document.getElementById('budget-after');
    var warningEl = document.getElementById('budget-warning');
    var budgetCard = document.getElementById('budget-card');
    var budgetStatusEl = document.getElementById('budget-status');
    var submitBtn = document.getElementById('publish-submit');
    var questionBuilder = document.getElementById('question-builder');
    var questionAddBtn = document.getElementById('question-add');
    var questionCountEl = document.getElementById('question-count');

    if (!rewardInput || !rewardEl || !totalEl || !balanceEl || !afterEl || !warningEl || !budgetCard || !budgetStatusEl || !submitBtn || !questionBuilder || !questionAddBtn || !questionCountEl) {
      return;
    }

    function updateBudget() {
      var reward = parseInt(rewardInput.value, 10);
      var balance = parseInt(balanceEl.textContent, 10);

      if (Number.isNaN(reward)) {
        reward = <?= SURVEY_DEFAULT_REWARD_POINTS ?>;
      }

      var total = reward * <?= SURVEY_REWARD_COST_MULTIPLIER ?>;
      var after = balance - total;
      var hasEnough = after >= 0;

      rewardEl.textContent = String(reward);
      totalEl.textContent = String(total);
      afterEl.textContent = String(after);

      submitBtn.disabled = !hasEnough;
      warningEl.hidden = hasEnough;
      budgetStatusEl.textContent = hasEnough ? 'Ready to publish' : 'Insufficient balance';
      budgetCard.classList.toggle('is-affordable', hasEnough);
      budgetCard.classList.toggle('is-tight', !hasEnough);
    }

    rewardInput.addEventListener('change', updateBudget);

    function relabelQuestions() {
      var rows = questionBuilder.querySelectorAll('.question-row');
      rows.forEach(function (row, i) {
        var label = row.querySelector('.field-label');
        var input = row.querySelector('input[name="questions[]"]');

        if (label) {
          label.textContent = 'Question ' + String(i + 1);
        }
        if (input) {
          input.id = 'question_' + String(i);
        }
        if (label && input) {
          label.setAttribute('for', input.id);
        }

        var optionsWrap = row.querySelector('.question-options-wrap');
        var typeSelect = row.querySelector('.question-type');

        if (!optionsWrap || !typeSelect) {
          return;
        }

        var isMultipleChoice = typeSelect.value === '<?= native_question_type_multiple_choice() ?>';
        optionsWrap.hidden = !isMultipleChoice;
      });

      questionCountEl.textContent = String(rows.length);
      questionAddBtn.disabled = rows.length >= <?= native_survey_question_max() ?>;
    }

    questionAddBtn.addEventListener('click', function () {
      var rows = questionBuilder.querySelectorAll('.question-row');
      if (rows.length >= <?= native_survey_question_max() ?>) {
        return;
      }

      var row = document.createElement('div');
      row.className = 'field question-row';
      row.innerHTML = '' +
        '<label class="field-label">Question</label>' +
        '<div class="actions-row submit-question-controls">' +
          '<input name="questions[]" type="text" class="input" maxlength="240" required>' +
          '<select name="question_types[]" class="select question-type">' +
            '<option value="<?= native_question_type_short_text() ?>">Short text</option>' +
            '<option value="<?= native_question_type_multiple_choice() ?>">Multiple choice</option>' +
          '</select>' +
          '<button type="button" class="btn btn-ghost btn-small question-remove">Remove</button>' +
        '</div>' +
        '<div class="field question-options-wrap" hidden>' +
          '<label class="field-label">Options (one per line)</label>' +
          '<textarea name="question_options[]" class="textarea question-options" rows="4" placeholder="Option A&#10;Option B&#10;Option C"></textarea>' +
        '</div>';

      questionBuilder.appendChild(row);
      relabelQuestions();
    });

    questionBuilder.addEventListener('click', function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains('question-remove')) {
        return;
      }

      var rows = questionBuilder.querySelectorAll('.question-row');
      if (rows.length <= <?= native_survey_question_min() ?>) {
        return;
      }

      var row = target.closest('.question-row');
      if (row) {
        row.remove();
        relabelQuestions();
      }
    });

    questionBuilder.addEventListener('change', function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains('question-type')) {
        return;
      }

      relabelQuestions();
    });

    relabelQuestions();
    updateBudget();
  })();
</script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
