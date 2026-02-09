/**
 * OnboardingWizard — 6-step student onboarding form
 * Replaces Laravel's EnrolmentController multi-step blade views.
 * Steps: Personal Info → Education → Employer → Requirements → PTR Quiz → Agreement
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import {
  fetchOnboardingState,
  saveOnboardingStep,
  completeOnboarding,
  fetchCountries,
  fetchPtrQuiz,
  savePtrQuizAnswer,
  type OnboardingState,
  type OnboardingStepData,
} from '@/lib/api';
import { useAuth } from '@/contexts/AuthContext';
import {
  ChevronLeft, ChevronRight, Check, Loader2, User, GraduationCap,
  Building2, FileText, ClipboardCheck, Shield, AlertTriangle,
} from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

// ─── Types ──────────────────────────────────────────────────────────────────

interface OnboardingWizardProps {
  studentId: number;
  studentName?: string;
  onComplete?: () => void;
}

interface FormErrors {
  [field: string]: string;
}

const STEP_ICONS = [User, GraduationCap, Building2, FileText, ClipboardCheck, Shield];

// ─── Step 1: Personal Info ──────────────────────────────────────────────────

function Step1PersonalInfo({ data, onChange, errors }: {
  data: OnboardingStepData;
  onChange: (d: OnboardingStepData) => void;
  errors: FormErrors;
}) {
  const [countries, setCountries] = useState<{ id: number; name: string }[]>([]);
  useEffect(() => { fetchCountries().then(setCountries).catch(() => {}); }, []);

  const set = (field: string, value: string) => onChange({ ...data, [field]: value });

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <FieldSelect label="Title" field="title" value={data.title} onChange={v => set('title', v)} error={errors.title}
          options={[{v:'Mr',l:'Mr'},{v:'Mrs',l:'Mrs'},{v:'Ms',l:'Ms'},{v:'Miss',l:'Miss'},{v:'Dr',l:'Dr'}]} required />
        <FieldSelect label="Gender" field="gender" value={data.gender} onChange={v => set('gender', v)} error={errors.gender}
          options={[{v:'male',l:'Male'},{v:'female',l:'Female'},{v:'other',l:'Other'},{v:'prefer_not',l:'Prefer not to say'}]} required />
        <FieldInput label="Date of Birth" field="dob" type="date" value={data.dob} onChange={v => set('dob', v)} error={errors.dob} required />
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FieldInput label="Home Phone" field="home_phone" value={data.home_phone} onChange={v => set('home_phone', v)} error={errors.home_phone} />
        <FieldInput label="Mobile" field="mobile" value={data.mobile} onChange={v => set('mobile', v)} error={errors.mobile} required />
      </div>

      <FieldInput label="Birthplace" field="birthplace" value={data.birthplace} onChange={v => set('birthplace', v)} error={errors.birthplace} required />

      <div className="border-t pt-4">
        <h4 className="text-sm font-semibold text-[#1e293b] mb-3">Emergency Contact</h4>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <FieldInput label="Contact Name" field="emergency_contact_name" value={data.emergency_contact_name} onChange={v => set('emergency_contact_name', v)} error={errors.emergency_contact_name} required />
          <FieldInput label="Relationship" field="relationship_to_you" value={data.relationship_to_you} onChange={v => set('relationship_to_you', v)} error={errors.relationship_to_you} required />
          <FieldInput label="Contact Number" field="emergency_contact_number" value={data.emergency_contact_number} onChange={v => set('emergency_contact_number', v)} error={errors.emergency_contact_number} required />
        </div>
      </div>

      <div className="border-t pt-4">
        <h4 className="text-sm font-semibold text-[#1e293b] mb-3">Address</h4>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FieldInput label="Residence Address" field="residence_address" value={data.residence_address} onChange={v => set('residence_address', v)} error={errors.residence_address} required />
          <FieldInput label="Postcode" field="residence_address_postcode" value={data.residence_address_postcode} onChange={v => set('residence_address_postcode', v)} error={errors.residence_address_postcode} required />
          <FieldInput label="Postal Address" field="postal_address" value={data.postal_address} onChange={v => set('postal_address', v)} error={errors.postal_address} required />
          <FieldInput label="Postcode" field="postal_address_postcode" value={data.postal_address_postcode} onChange={v => set('postal_address_postcode', v)} error={errors.postal_address_postcode} required />
        </div>
      </div>

      <div className="border-t pt-4">
        <h4 className="text-sm font-semibold text-[#1e293b] mb-3">Background</h4>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FieldSelect label="Country of Birth" field="country" value={data.country} onChange={v => set('country', v)} error={errors.country}
            options={countries.map(c => ({ v: String(c.id), l: c.name }))} required />
          <FieldSelect label="Main Language" field="language" value={data.language} onChange={v => set('language', v)} error={errors.language}
            options={[{v:'en',l:'English'},{v:'other',l:'Other'}]} required />
          {data.language === 'other' && (
            <>
              <FieldInput label="Other Language" field="language_other" value={data.language_other} onChange={v => set('language_other', v)} error={errors.language_other} required />
              <FieldSelect label="English Proficiency" field="english_proficiency" value={data.english_proficiency} onChange={v => set('english_proficiency', v)} error={errors.english_proficiency}
                options={[{v:'very_well',l:'Very Well'},{v:'well',l:'Well'},{v:'not_well',l:'Not Well'},{v:'not_at_all',l:'Not at All'}]} required />
            </>
          )}
          <FieldSelect label="Aboriginal/Torres Strait Islander" field="torres_island" value={data.torres_island} onChange={v => set('torres_island', v)} error={errors.torres_island}
            options={[{v:'1',l:'No'},{v:'2',l:'Yes, Aboriginal'},{v:'3',l:'Yes, Torres Strait Islander'},{v:'4',l:'Yes, Both'}]} required />
          <FieldSelect label="Do you have a disability?" field="has_disability" value={data.has_disability} onChange={v => set('has_disability', v)} error={errors.has_disability}
            options={[{v:'no',l:'No'},{v:'yes',l:'Yes'}]} required />
          {data.has_disability === 'yes' && (
            <>
              <FieldSelect label="Disability Type" field="disabilities" value={data.disabilities} onChange={v => set('disabilities', v)} error={errors.disabilities}
                options={[{v:'1',l:'Hearing/Deaf'},{v:'2',l:'Physical'},{v:'3',l:'Intellectual'},{v:'4',l:'Learning'},{v:'5',l:'Mental Illness'},{v:'6',l:'Acquired Brain Impairment'},{v:'7',l:'Vision'},{v:'8',l:'Medical Condition'},{v:'9',l:'Other'}]} required />
              <FieldSelect label="Need Assistance?" field="need_assistance" value={data.need_assistance} onChange={v => set('need_assistance', v)} error={errors.need_assistance}
                options={[{v:'no',l:'No'},{v:'yes',l:'Yes'}]} required />
            </>
          )}
        </div>
      </div>

      <div className="border-t pt-4">
        <h4 className="text-sm font-semibold text-[#1e293b] mb-3">Employment</h4>
        <FieldSelect label="Employment Status" field="employment" value={data.employment} onChange={v => set('employment', v)} error={errors.employment}
          options={[{v:'1',l:'Full-time employee'},{v:'2',l:'Part-time employee'},{v:'3',l:'Self-employed'},{v:'4',l:'Employer'},{v:'5',l:'Employed - unpaid in family business'},{v:'6',l:'Unemployed - seeking full-time'},{v:'7',l:'Unemployed - seeking part-time'},{v:'8',l:'Unemployed - not seeking'}]} required />
      </div>
    </div>
  );
}

// ─── Step 2: Education Details ──────────────────────────────────────────────

function Step2Education({ data, onChange, errors }: {
  data: OnboardingStepData;
  onChange: (d: OnboardingStepData) => void;
  errors: FormErrors;
}) {
  const set = (field: string, value: string) => onChange({ ...data, [field]: value });

  return (
    <div className="space-y-6">
      <FieldSelect label="Highest School Level Completed" field="school_level" value={data.school_level} onChange={v => set('school_level', v)} error={errors.school_level}
        options={[{v:'1',l:'Did not go to school'},{v:'2',l:'Year 8 or below'},{v:'3',l:'Year 9 or equivalent'},{v:'4',l:'Year 10 or equivalent'},{v:'5',l:'Year 11 or equivalent'},{v:'6',l:'Year 12 or equivalent'}]} required />

      <FieldSelect label="Are you still at secondary school?" field="secondary_level" value={data.secondary_level} onChange={v => set('secondary_level', v)} error={errors.secondary_level}
        options={[{v:'yes',l:'Yes'},{v:'no',l:'No'}]} required />

      <FieldSelect label="Have you completed any additional qualifications?" field="additional_qualification" value={data.additional_qualification} onChange={v => set('additional_qualification', v)} error={errors.additional_qualification}
        options={[{v:'yes',l:'Yes'},{v:'no',l:'No'}]} required />

      {data.additional_qualification === 'yes' && (
        <div className="border rounded-lg p-4 bg-[#f8fafc] space-y-4">
          <p className="text-xs text-[#64748b]">Select where you obtained each qualification (leave blank if not applicable)</p>
          {[
            { field: 'higher_degree', label: 'Bachelor Degree or Higher' },
            { field: 'advanced_diploma', label: 'Advanced Diploma' },
            { field: 'diploma', label: 'Diploma' },
            { field: 'certificate4', label: 'Certificate IV' },
            { field: 'certificate3', label: 'Certificate III' },
            { field: 'certificate2', label: 'Certificate II' },
            { field: 'certificate1', label: 'Certificate I' },
            { field: 'certificate_any', label: 'Other Certificate' },
          ].map(q => (
            <FieldSelect key={q.field} label={q.label} field={q.field} value={data[q.field]} onChange={v => set(q.field, v)} error={errors[q.field]}
              options={[{v:'',l:'— Not applicable —'},{v:'1',l:'In Australia'},{v:'2',l:'Outside Australia'},{v:'3',l:'Both'}]} />
          ))}
          {data.certificate_any && data.certificate_any !== '' && (
            <FieldInput label="Certificate Details" field="certificate_any_details" value={data.certificate_any_details} onChange={v => set('certificate_any_details', v)} error={errors.certificate_any_details} />
          )}
        </div>
      )}
    </div>
  );
}

// ─── Step 3: Employer Details ───────────────────────────────────────────────

function Step3Employer({ data, onChange, errors }: {
  data: OnboardingStepData;
  onChange: (d: OnboardingStepData) => void;
  errors: FormErrors;
}) {
  const set = (field: string, value: string) => onChange({ ...data, [field]: value });

  return (
    <div className="space-y-6">
      <p className="text-sm text-[#64748b]">Enter your employer details if applicable. All fields are optional.</p>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FieldInput label="Organisation Name" field="organization_name" value={data.organization_name} onChange={v => set('organization_name', v)} error={errors.organization_name} />
        <FieldInput label="Your Position" field="your_position" value={data.your_position} onChange={v => set('your_position', v)} error={errors.your_position} />
        <FieldInput label="Supervisor Name" field="supervisor_name" value={data.supervisor_name} onChange={v => set('supervisor_name', v)} error={errors.supervisor_name} />
        <FieldInput label="Street Address" field="street_address" value={data.street_address} onChange={v => set('street_address', v)} error={errors.street_address} />
        <FieldInput label="Postcode" field="postcode" value={data.postcode} onChange={v => set('postcode', v)} error={errors.postcode} />
        <FieldInput label="Telephone" field="telephone" value={data.telephone} onChange={v => set('telephone', v)} error={errors.telephone} />
        <FieldInput label="Email" field="email" type="email" value={data.email} onChange={v => set('email', v)} error={errors.email} />
        <FieldInput label="Website" field="website" value={data.website} onChange={v => set('website', v)} error={errors.website} />
      </div>
    </div>
  );
}

// ─── Step 4: Requirements ───────────────────────────────────────────────────

function Step4Requirements({ data, onChange, errors }: {
  data: OnboardingStepData;
  onChange: (d: OnboardingStepData) => void;
  errors: FormErrors;
}) {
  const set = (field: string, value: string) => onChange({ ...data, [field]: value });

  return (
    <div className="space-y-6">
      <FieldSelect label="Main reason for undertaking this study" field="study_reason" value={data.study_reason} onChange={v => set('study_reason', v)} error={errors.study_reason}
        options={[
          {v:'1',l:'To get a job'},{v:'2',l:'To develop my existing business'},{v:'3',l:'To start my own business'},
          {v:'4',l:'To try for a different career'},{v:'5',l:'To get a better job or promotion'},
          {v:'6',l:'It was a requirement of my job'},{v:'7',l:'I wanted extra skills for my job'},
          {v:'8',l:'To get into another course of study'},{v:'11',l:'For personal interest or self-development'},
          {v:'12',l:'To get skills for community/voluntary work'},{v:'13',l:'Other reasons'},
        ]} required />

      <FieldInput label="USI Number" field="usi_number" value={data.usi_number} onChange={v => set('usi_number', v)} error={errors.usi_number}
        placeholder="Enter your Unique Student Identifier" />

      <FieldSelect label="Nominate USI" field="nominate_usi" value={data.nominate_usi} onChange={v => set('nominate_usi', v)} error={errors.nominate_usi}
        options={[{v:'1',l:'I give permission for the training provider to obtain a USI on my behalf'},{v:'2',l:'I already have a USI'}]} required />

      {!data.usi_number && (
        <div className="border rounded-lg p-4 bg-amber-50 border-amber-200">
          <p className="text-sm text-amber-800 mb-3">If you don't have a USI, please provide two forms of identification:</p>
          <div className="space-y-4">
            <FieldSelect label="Document 1 Type" field="document1_type" value={data.document1_type} onChange={v => set('document1_type', v)} error={errors.document1_type}
              options={[{v:'1',l:'Driver\'s Licence'},{v:'2',l:'Passport'},{v:'3',l:'Birth Certificate'},{v:'4',l:'Medicare Card'},{v:'5',l:'Certificate of Registration by Descent'},{v:'6',l:'Citizenship Certificate'},{v:'7',l:'ImmiCard'}]} />
            <FieldSelect label="Document 2 Type" field="document2_type" value={data.document2_type} onChange={v => set('document2_type', v)} error={errors.document2_type}
              options={[{v:'1',l:'Driver\'s Licence'},{v:'2',l:'Passport'},{v:'3',l:'Birth Certificate'},{v:'4',l:'Medicare Card'},{v:'5',l:'Certificate of Registration by Descent'},{v:'6',l:'Citizenship Certificate'},{v:'7',l:'ImmiCard'}]} />
            <p className="text-xs text-[#94a3b8]">Document upload is handled separately via the Documents tab.</p>
          </div>
        </div>
      )}
    </div>
  );
}

// ─── Step 5: PTR Quiz ───────────────────────────────────────────────────────

function Step5PtrQuiz({ studentId, ptrExcluded, ptrCompleted, onComplete }: {
  studentId: number;
  ptrExcluded: boolean;
  ptrCompleted: boolean;
  onComplete: () => void;
}) {
  const [quiz, setQuiz] = useState<any>(null);
  const [currentQ, setCurrentQ] = useState(0);
  const [answers, setAnswers] = useState<Record<string, any>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [courseId, setCourseId] = useState<number>(0);
  const [completed, setCompleted] = useState(ptrCompleted);

  useEffect(() => {
    (async () => {
      const { checkPtrRequirement: checkPtr } = await import('@/lib/api');
      const ptrCheck = await checkPtr(studentId);
      setCourseId(ptrCheck.courseId ?? 0);
      if (ptrCheck.excluded || ptrCheck.completed) {
        setCompleted(true);
        setLoading(false);
        return;
      }
      if (ptrCheck.courseId) {
        const result = await fetchPtrQuiz(studentId, ptrCheck.courseId);
        setQuiz(result.quiz);
        if (result.alreadyCompleted) setCompleted(true);
        if (result.existingAttempt) {
          const existing = typeof result.existingAttempt.submitted_answers === 'string'
            ? JSON.parse(result.existingAttempt.submitted_answers)
            : result.existingAttempt.submitted_answers ?? {};
          setAnswers(existing);
          setCurrentQ(Object.keys(existing).length);
        }
      }
      setLoading(false);
    })();
  }, [studentId]);

  if (ptrExcluded || completed) {
    return (
      <div className="text-center py-12">
        <Check className="mx-auto h-12 w-12 text-emerald-500 mb-4" />
        <h3 className="text-lg font-semibold text-[#1e293b]">
          {ptrExcluded ? 'Pre-Training Review Not Required' : 'Pre-Training Review Completed'}
        </h3>
        <p className="text-sm text-[#64748b] mt-2">
          {ptrExcluded ? 'This course category does not require a PTR quiz.' : 'The PTR quiz has already been submitted.'}
        </p>
        <Button className="mt-6" onClick={onComplete}>
          Continue to Agreement <ChevronRight className="w-4 h-4 ml-1" />
        </Button>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="py-12 text-center">
        <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
        <p className="mt-2 text-sm text-[#64748b]">Loading PTR quiz...</p>
      </div>
    );
  }

  if (!quiz || quiz.questions.length === 0) {
    return (
      <div className="text-center py-12">
        <AlertTriangle className="mx-auto h-10 w-10 text-amber-500 mb-3" />
        <p className="text-sm text-[#64748b]">No PTR quiz found. Contact your administrator.</p>
        <Button variant="outline" className="mt-4" onClick={onComplete}>Skip & Continue</Button>
      </div>
    );
  }

  const questions = quiz.questions;
  const question = questions[currentQ];
  const progress = Math.round((Object.keys(answers).length / questions.length) * 100);

  async function handleAnswer(answer: any) {
    if (!question || !courseId) return;
    setSaving(true);
    try {
      const result = await savePtrQuizAnswer(studentId, courseId, question.id, answer);
      setAnswers(prev => ({ ...prev, [String(question.id)]: answer }));
      if (result.isComplete) {
        setCompleted(true);
        toast.success('PTR quiz submitted successfully');
        onComplete();
      } else {
        setCurrentQ(prev => Math.min(prev + 1, questions.length - 1));
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save answer');
    } finally {
      setSaving(false);
    }
  }

  if (!question) return null;

  const options = typeof question.options === 'string' ? JSON.parse(question.options) : question.options;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <span className="text-sm text-[#64748b]">Question {currentQ + 1} of {questions.length}</span>
        <span className="text-sm font-medium text-[#3b82f6]">{progress}%</span>
      </div>
      <Progress value={progress} className="h-2" />

      <Card className="border-[#e2e8f0]">
        <CardContent className="pt-6">
          <h4 className="font-medium text-[#1e293b] mb-2">{question.title}</h4>
          {question.content && (
            <div className="text-sm text-[#64748b] mb-4" dangerouslySetInnerHTML={{ __html: question.content }} />
          )}

          {question.answer_type === 'SINGLE' && options && (
            <div className="space-y-2 mt-4">
              {Object.entries(options).map(([key, label]) => (
                <button
                  key={key}
                  onClick={() => handleAnswer(key)}
                  disabled={saving}
                  className={cn(
                    'w-full text-left px-4 py-3 rounded-lg border transition-colors',
                    answers[String(question.id)] === key
                      ? 'border-[#3b82f6] bg-blue-50 text-[#1e293b]'
                      : 'border-[#e2e8f0] hover:border-[#94a3b8] text-[#64748b]'
                  )}
                >
                  <span className="text-sm">{String(label)}</span>
                </button>
              ))}
            </div>
          )}

          {question.answer_type === 'MCQ' && options && (
            <div className="space-y-2 mt-4">
              {Object.entries(options).map(([key, label]) => {
                const current = answers[String(question.id)];
                const selected = Array.isArray(current) ? current.includes(key) : false;
                return (
                  <button
                    key={key}
                    onClick={() => {
                      const prev = Array.isArray(current) ? current : [];
                      const next = selected ? prev.filter((k: string) => k !== key) : [...prev, key];
                      setAnswers(a => ({ ...a, [String(question.id)]: next }));
                    }}
                    disabled={saving}
                    className={cn(
                      'w-full text-left px-4 py-3 rounded-lg border transition-colors',
                      selected ? 'border-[#3b82f6] bg-blue-50' : 'border-[#e2e8f0] hover:border-[#94a3b8]'
                    )}
                  >
                    <span className="text-sm">{String(label)}</span>
                  </button>
                );
              })}
              <Button className="mt-3" disabled={saving || !answers[String(question.id)]?.length}
                onClick={() => handleAnswer(answers[String(question.id)])}>
                {saving ? <Loader2 className="w-4 h-4 animate-spin mr-1" /> : null}
                Confirm Answer
              </Button>
            </div>
          )}

          {(question.answer_type === 'TEXT' || question.answer_type === 'TEXTAREA') && (
            <div className="mt-4">
              {question.answer_type === 'TEXTAREA' ? (
                <textarea
                  className="w-full border rounded-lg px-3 py-2 text-sm min-h-[100px]"
                  value={answers[String(question.id)] ?? ''}
                  onChange={e => setAnswers(a => ({ ...a, [String(question.id)]: e.target.value }))}
                />
              ) : (
                <input
                  type="text"
                  className="w-full border rounded-lg px-3 py-2 text-sm"
                  value={answers[String(question.id)] ?? ''}
                  onChange={e => setAnswers(a => ({ ...a, [String(question.id)]: e.target.value }))}
                />
              )}
              <Button className="mt-3" disabled={saving || !answers[String(question.id)]}
                onClick={() => handleAnswer(answers[String(question.id)])}>
                {saving ? <Loader2 className="w-4 h-4 animate-spin mr-1" /> : null}
                Submit Answer
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {currentQ > 0 && (
        <Button variant="outline" size="sm" onClick={() => setCurrentQ(prev => prev - 1)}>
          <ChevronLeft className="w-4 h-4 mr-1" /> Previous Question
        </Button>
      )}
    </div>
  );
}

// ─── Step 6: Agreement ──────────────────────────────────────────────────────

function Step6Agreement({ data, onChange, errors }: {
  data: OnboardingStepData;
  onChange: (d: OnboardingStepData) => void;
  errors: FormErrors;
}) {
  return (
    <div className="space-y-6">
      <Card className="border-[#e2e8f0] bg-[#f8fafc]">
        <CardContent className="pt-6">
          <h4 className="font-semibold text-[#1e293b] mb-3">Applicant Declarations and Consent</h4>
          <div className="text-sm text-[#64748b] space-y-2 max-h-[300px] overflow-y-auto pr-2">
            <p>By accepting this agreement, I declare that:</p>
            <ul className="list-disc pl-5 space-y-1">
              <li>The information I have provided in this enrolment form is true and correct to the best of my knowledge.</li>
              <li>I understand that providing false or misleading information may result in cancellation of my enrolment.</li>
              <li>I have read and understood the Student Handbook and agree to abide by its terms and conditions.</li>
              <li>I consent to the collection, use and disclosure of my personal information as outlined in the Privacy Policy.</li>
              <li>I understand my rights and responsibilities as a student, including attendance requirements and assessment obligations.</li>
              <li>I agree to comply with all relevant policies and procedures of the training organisation.</li>
              <li>I acknowledge that I have been informed of the fees and charges associated with my course of study.</li>
              <li>I consent to having my results reported to the relevant government authority as required by law.</li>
            </ul>
          </div>
        </CardContent>
      </Card>

      <div className="flex items-start gap-3 p-4 border rounded-lg">
        <input
          type="checkbox"
          id="agreement"
          checked={data.agreement === 'yes'}
          onChange={e => onChange({ ...data, agreement: e.target.checked ? 'yes' : '' })}
          className="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#3b82f6] focus:ring-[#3b82f6]"
        />
        <label htmlFor="agreement" className="text-sm text-[#1e293b]">
          I have read and agree to the above Applicant Declarations and Consent. I understand that this constitutes my electronic signature.
        </label>
      </div>
      {errors.agreement && <p className="text-xs text-red-500">{errors.agreement}</p>}
    </div>
  );
}

// ─── Reusable Form Fields ───────────────────────────────────────────────────

function FieldInput({ label, field, value, onChange, error, type = 'text', required, placeholder }: {
  label: string; field: string; value: string; onChange: (v: string) => void;
  error?: string; type?: string; required?: boolean; placeholder?: string;
}) {
  return (
    <div>
      <label className="block text-xs font-medium text-[#64748b] mb-1">
        {label}{required && <span className="text-red-500 ml-0.5">*</span>}
      </label>
      <input
        type={type}
        value={value ?? ''}
        onChange={e => onChange(e.target.value)}
        placeholder={placeholder}
        className={cn(
          'w-full border rounded-lg px-3 py-2 text-sm transition-colors',
          error ? 'border-red-300 focus:ring-red-500' : 'border-[#e2e8f0] focus:ring-[#3b82f6]',
          'focus:outline-none focus:ring-2 focus:ring-offset-0'
        )}
      />
      {error && <p className="text-xs text-red-500 mt-0.5">{error}</p>}
    </div>
  );
}

function FieldSelect({ label, field, value, onChange, error, options, required }: {
  label: string; field: string; value: string; onChange: (v: string) => void;
  error?: string; options: { v: string; l: string }[]; required?: boolean;
}) {
  return (
    <div>
      <label className="block text-xs font-medium text-[#64748b] mb-1">
        {label}{required && <span className="text-red-500 ml-0.5">*</span>}
      </label>
      <select
        value={value ?? ''}
        onChange={e => onChange(e.target.value)}
        className={cn(
          'w-full border rounded-lg px-3 py-2 text-sm transition-colors bg-white',
          error ? 'border-red-300 focus:ring-red-500' : 'border-[#e2e8f0] focus:ring-[#3b82f6]',
          'focus:outline-none focus:ring-2 focus:ring-offset-0'
        )}
      >
        <option value="">— Select —</option>
        {options.map(o => <option key={o.v} value={o.v}>{o.l}</option>)}
      </select>
      {error && <p className="text-xs text-red-500 mt-0.5">{error}</p>}
    </div>
  );
}

// ─── Validation ─────────────────────────────────────────────────────────────

function validateStep(step: number, data: OnboardingStepData): FormErrors {
  const errors: FormErrors = {};
  const req = (field: string, label: string) => {
    if (!data[field] || String(data[field]).trim() === '') errors[field] = `${label} is required`;
  };

  if (step === 1) {
    req('title', 'Title'); req('gender', 'Gender'); req('dob', 'Date of Birth');
    req('mobile', 'Mobile'); req('birthplace', 'Birthplace');
    req('emergency_contact_name', 'Emergency Contact Name');
    req('relationship_to_you', 'Relationship');
    req('emergency_contact_number', 'Emergency Contact Number');
    req('residence_address', 'Residence Address');
    req('residence_address_postcode', 'Residence Postcode');
    req('postal_address', 'Postal Address');
    req('postal_address_postcode', 'Postal Postcode');
    req('country', 'Country'); req('language', 'Language');
    req('torres_island', 'Aboriginal/TSI'); req('has_disability', 'Disability');
    req('employment', 'Employment');
    if (data.language === 'other') {
      req('language_other', 'Other Language'); req('english_proficiency', 'English Proficiency');
    }
    if (data.has_disability === 'yes') {
      req('disabilities', 'Disability Type');
    }
    if (data.dob) {
      const age = (Date.now() - new Date(data.dob).getTime()) / (365.25 * 24 * 60 * 60 * 1000);
      if (age < 15) errors.dob = 'You must be 15 years or above';
    }
  } else if (step === 2) {
    req('school_level', 'School Level'); req('secondary_level', 'Secondary School');
    req('additional_qualification', 'Additional Qualification');
  } else if (step === 4) {
    req('study_reason', 'Study Reason'); req('nominate_usi', 'Nominate USI');
  } else if (step === 6) {
    if (data.agreement !== 'yes') errors.agreement = 'You must accept the agreement to proceed';
  }
  return errors;
}

// ─── Main Component ─────────────────────────────────────────────────────────

export function OnboardingWizard({ studentId, studentName, onComplete }: OnboardingWizardProps) {
  const { user: authUser } = useAuth();
  const [state, setState] = useState<OnboardingState | null>(null);
  const [activeStep, setActiveStep] = useState(1);
  const [stepData, setStepData] = useState<Record<number, OnboardingStepData>>({});
  const [errors, setErrors] = useState<FormErrors>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const loadState = useCallback(async () => {
    setLoading(true);
    try {
      const s = await fetchOnboardingState(studentId);
      setState(s);
      // Pre-populate step data from existing enrolment
      const existing: Record<number, OnboardingStepData> = {};
      for (let i = 1; i <= 6; i++) {
        if (s.stepData[`step-${i}`]) existing[i] = s.stepData[`step-${i}`];
      }
      setStepData(prev => ({ ...existing, ...prev }));
      setActiveStep(s.currentStep || 1);
    } catch (err) {
      toast.error('Failed to load onboarding state');
    } finally {
      setLoading(false);
    }
  }, [studentId]);

  useEffect(() => { loadState(); }, [loadState]);

  const currentData = stepData[activeStep] ?? {};
  const setCurrentData = (d: OnboardingStepData) => setStepData(prev => ({ ...prev, [activeStep]: d }));

  async function handleNext() {
    // Validate current step
    const validationErrors = validateStep(activeStep, currentData);
    setErrors(validationErrors);
    if (Object.keys(validationErrors).length > 0) {
      toast.error('Please fix the errors before proceeding');
      return;
    }

    setSaving(true);
    try {
      if (activeStep === 6) {
        // Complete onboarding
        await completeOnboarding(
          studentId,
          { agreement: currentData.agreement, signed_on: new Date().toISOString() },
          authUser?.id ?? 0,
        );
        toast.success('Onboarding completed successfully!');
        onComplete?.();
        return;
      }

      const result = await saveOnboardingStep(studentId, activeStep, currentData);
      setErrors({});

      if (result.nextStep === 0) {
        toast.success('Onboarding completed!');
        onComplete?.();
      } else {
        setActiveStep(result.nextStep);
        // Refresh state
        const s = await fetchOnboardingState(studentId);
        setState(s);
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save step');
    } finally {
      setSaving(false);
    }
  }

  function handleBack() {
    if (activeStep <= 1) return;
    let prev = activeStep - 1;
    // Skip step 5 if PTR is excluded
    if (prev === 5 && state?.ptrExcluded) prev = 4;
    setActiveStep(prev);
    setErrors({});
  }

  if (loading) {
    return (
      <div className="py-12 text-center">
        <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
        <p className="mt-2 text-sm text-[#64748b]">Loading onboarding...</p>
      </div>
    );
  }

  if (state && state.currentStep === 0) {
    return (
      <Card className="p-8 text-center border-emerald-200 bg-emerald-50">
        <Check className="mx-auto h-12 w-12 text-emerald-500 mb-4" />
        <h3 className="text-xl font-bold text-[#1e293b]">Onboarding Complete</h3>
        <p className="text-sm text-[#64748b] mt-2">
          {studentName ? `${studentName} has` : 'Student has'} completed all onboarding steps.
        </p>
      </Card>
    );
  }

  const progressPct = state ? Math.round(
    (state.steps.filter(s => s.completed).length / state.steps.length) * 100
  ) : 0;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h3 className="font-heading font-semibold text-[#1e293b]">
          Student Onboarding{studentName ? ` — ${studentName}` : ''}
        </h3>
        <p className="text-xs text-[#94a3b8]">Complete all steps to finalize enrolment</p>
      </div>

      {/* Step Indicator */}
      <div className="flex items-center gap-1 overflow-x-auto pb-2">
        {(state?.steps ?? []).map((step, idx) => {
          const Icon = STEP_ICONS[idx] ?? FileText;
          const isActive = step.number === activeStep;
          const isCompleted = step.completed;
          const isDisabled = step.disabled;

          return (
            <button
              key={step.number}
              onClick={() => {
                if (!isDisabled && (isCompleted || step.number <= activeStep)) {
                  setActiveStep(step.number);
                  setErrors({});
                }
              }}
              disabled={isDisabled}
              className={cn(
                'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-all flex-shrink-0',
                isActive && 'bg-[#3b82f6] text-white shadow-sm',
                !isActive && isCompleted && 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200',
                !isActive && !isCompleted && !isDisabled && 'bg-[#f1f5f9] text-[#94a3b8]',
                isDisabled && 'bg-[#f1f5f9] text-[#cbd5e1] cursor-not-allowed opacity-50',
              )}
            >
              {isCompleted && !isActive ? (
                <Check className="w-3.5 h-3.5" />
              ) : (
                <Icon className="w-3.5 h-3.5" />
              )}
              <span className="hidden sm:inline">{step.title}</span>
              <span className="sm:hidden">#{step.number}</span>
            </button>
          );
        })}
      </div>

      {/* Progress bar */}
      <Progress value={progressPct} className="h-1.5" />

      {/* Step Content */}
      <Card className="border-[#e2e8f0]/50 shadow-card">
        <CardHeader className="pb-2">
          <CardTitle className="text-base text-[#3b82f6] flex items-center gap-2">
            {(() => { const I = STEP_ICONS[activeStep - 1] ?? FileText; return <I className="w-4 h-4" />; })()}
            Step {activeStep}: {state?.steps.find(s => s.number === activeStep)?.title}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {activeStep === 1 && <Step1PersonalInfo data={currentData} onChange={setCurrentData} errors={errors} />}
          {activeStep === 2 && <Step2Education data={currentData} onChange={setCurrentData} errors={errors} />}
          {activeStep === 3 && <Step3Employer data={currentData} onChange={setCurrentData} errors={errors} />}
          {activeStep === 4 && <Step4Requirements data={currentData} onChange={setCurrentData} errors={errors} />}
          {activeStep === 5 && (
            <Step5PtrQuiz
              studentId={studentId}
              ptrExcluded={state?.ptrExcluded ?? false}
              ptrCompleted={state?.ptrCompleted ?? false}
              onComplete={() => {
                saveOnboardingStep(studentId, 5, { quiz_completed: true, completed_at: Date.now() })
                  .then(r => { setActiveStep(r.nextStep || 6); loadState(); });
              }}
            />
          )}
          {activeStep === 6 && <Step6Agreement data={currentData} onChange={setCurrentData} errors={errors} />}
        </CardContent>
      </Card>

      {/* Navigation */}
      {activeStep !== 5 && (
        <div className="flex items-center justify-between">
          <Button variant="outline" onClick={handleBack} disabled={activeStep <= 1 || saving}>
            <ChevronLeft className="w-4 h-4 mr-1" /> Back
          </Button>
          <Button onClick={handleNext} disabled={saving}>
            {saving && <Loader2 className="w-4 h-4 animate-spin mr-1" />}
            {activeStep === 6 ? 'Complete Onboarding' : 'Save & Continue'}
            {activeStep < 6 && <ChevronRight className="w-4 h-4 ml-1" />}
          </Button>
        </div>
      )}
    </div>
  );
}
