<script type="text/javascript">
    (function () {
        var form = document.getElementById('reg-form');
        var wizard = document.getElementById('seller-reg-wizard');
        var studentStatus = document.getElementById('student_status');
        var studentStepButton = wizard ? wizard.querySelector('[data-student-step]') : null;
        var studentFields = wizard ? wizard.querySelectorAll('[data-student-required]') : [];
        var steps = [
            { id: 'seller-step-personal', button: wizard ? wizard.querySelector('[data-step-target="seller-step-personal"]') : null },
            { id: 'seller-step-student', button: wizard ? wizard.querySelector('[data-step-target="seller-step-student"]') : null, studentOnly: true },
            { id: 'seller-step-business', button: wizard ? wizard.querySelector('[data-step-target="seller-step-business"]') : null }
        ];
        var currentStepId = 'seller-step-personal';

        if (!form || !wizard || !studentStatus) {
            return;
        }

        function isStudent() {
            return studentStatus.value === 'student';
        }

        function visibleSteps() {
            return steps.filter(function (step) {
                return !step.studentOnly || isStudent();
            });
        }

        function syncStudentStep() {
            var showStudent = isStudent();
            if (studentStepButton) {
                studentStepButton.style.display = showStudent ? '' : 'none';
            }

            Array.prototype.forEach.call(studentFields, function (field) {
                field.required = showStudent;
                if (!showStudent && field.type !== 'file') {
                    field.value = '';
                }
                if (!showStudent && field.type === 'file') {
                    field.value = '';
                }
            });
        }

        function renderStep(stepId) {
            currentStepId = stepId;
            var currentVisibleSteps = visibleSteps();
            var activeIndex = currentVisibleSteps.findIndex(function (step) {
                return step.id === stepId;
            });

            steps.forEach(function (step) {
                var panel = document.getElementById(step.id);
                var isVisible = !step.studentOnly || isStudent();
                var isActive = step.id === stepId;
                var index = currentVisibleSteps.findIndex(function (item) {
                    return item.id === step.id;
                });
                var isComplete = index > -1 && activeIndex > index;

                if (panel) {
                    panel.classList.toggle('is-active', isActive);
                    panel.style.display = isVisible && isActive ? 'block' : 'none';
                }

                if (step.button) {
                    step.button.classList.toggle('is-active', isActive);
                    step.button.classList.toggle('is-complete', isComplete);
                    step.button.style.display = isVisible ? '' : 'none';
                }
            });
        }

        function canOpenStep(stepId) {
            var currentVisibleSteps = visibleSteps();
            var targetIndex = currentVisibleSteps.findIndex(function (step) {
                return step.id === stepId;
            });

            if (targetIndex < 0) {
                return false;
            }

            for (var i = 0; i < targetIndex; i++) {
                if (!validateStep(currentVisibleSteps[i].id)) {
                    renderStep(currentVisibleSteps[i].id);
                    return false;
                }
            }

            return true;
        }

        function validateStep(stepId) {
            var panel = document.getElementById(stepId);
            if (!panel) {
                return true;
            }

            var fields = panel.querySelectorAll('input, select, textarea');
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i];
                if (field.disabled || field.type === 'hidden' || !field.required) {
                    continue;
                }
                if (!field.reportValidity()) {
                    return false;
                }
            }

            if (stepId === 'seller-step-personal') {
                var password = form.querySelector('input[name="password"]');
                var confirmation = form.querySelector('input[name="password_confirmation"]');
                if (password && confirmation && password.value !== confirmation.value) {
                    confirmation.setCustomValidity('{{ translate('Passwords do not match.') }}');
                    confirmation.reportValidity();
                    confirmation.setCustomValidity('');
                    return false;
                }
            }

            return true;
        }

        function firstErrorStep() {
            return visibleSteps().find(function (step) {
                var panel = document.getElementById(step.id);
                return panel && panel.querySelector('.is-invalid, .invalid-feedback');
            });
        }

        wizard.querySelectorAll('[data-step-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = button.getAttribute('data-step-target');
                if (target && canOpenStep(target)) {
                    renderStep(target);
                }
            });
        });

        var nextPersonal = wizard.querySelector('[data-next-from="personal"]');
        if (nextPersonal) {
            nextPersonal.addEventListener('click', function () {
                if (validateStep('seller-step-personal')) {
                    renderStep(isStudent() ? 'seller-step-student' : 'seller-step-business');
                }
            });
        }

        var nextStudent = wizard.querySelector('[data-next-from="student"]');
        if (nextStudent) {
            nextStudent.addEventListener('click', function () {
                if (validateStep('seller-step-student')) {
                    renderStep('seller-step-business');
                }
            });
        }

        var prevStudent = wizard.querySelector('[data-prev-from="student"]');
        if (prevStudent) {
            prevStudent.addEventListener('click', function () {
                renderStep('seller-step-personal');
            });
        }

        var prevBusiness = wizard.querySelector('[data-prev-from="business"]');
        if (prevBusiness) {
            prevBusiness.addEventListener('click', function () {
                renderStep(isStudent() ? 'seller-step-student' : 'seller-step-personal');
            });
        }

        studentStatus.addEventListener('change', function () {
            syncStudentStep();
            if (!isStudent() && currentStepId === 'seller-step-student') {
                renderStep('seller-step-personal');
                return;
            }
            renderStep(currentStepId);
        });

        form.addEventListener('submit', function (event) {
            var invalidStep = visibleSteps().find(function (step) {
                return !validateStep(step.id);
            });

            if (invalidStep) {
                event.preventDefault();
                renderStep(invalidStep.id);
            }
        });

        syncStudentStep();

        var errorStep = firstErrorStep();
        if (errorStep) {
            renderStep(errorStep.id);
        } else {
            renderStep('seller-step-personal');
        }
    })();
</script>

@if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_seller_register') == 1)
    <script src="https://www.google.com/recaptcha/api.js?render={{ env('CAPTCHA_KEY') }}"></script>
    
    <script type="text/javascript">
        document.getElementById('reg-form').addEventListener('submit', function(e) {
            e.preventDefault();
            grecaptcha.ready(function() {
                grecaptcha.execute(`{{ env('CAPTCHA_KEY') }}`, {action: 'seller_registration'}).then(function(token) {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'hidden');
                    input.setAttribute('name', 'g-recaptcha-response');
                    input.setAttribute('value', token);
                    e.target.appendChild(input);

                    e.target.submit();
                });
            });
        });
    </script>
@endif
