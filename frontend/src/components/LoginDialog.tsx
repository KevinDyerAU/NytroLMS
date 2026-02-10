/**
 * LoginDialog - Supabase Auth login/signup/reset modal
 * Design: NytroAI-style auth flow
 */
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Eye, EyeOff, AlertCircle, Mail, CheckCircle, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { toast } from 'sonner';

interface LoginDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

type DialogView = 'login' | 'forgot' | 'signup' | 'check-email';

export function LoginDialog({ open, onOpenChange }: LoginDialogProps) {
  const navigate = useNavigate();
  const { login, signUp, resetPassword, isLoading } = useAuth();
  const [view, setView] = useState<DialogView>('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [emailError, setEmailError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [firstNameError, setFirstNameError] = useState('');
  const [lastNameError, setLastNameError] = useState('');
  const [shakeError, setShakeError] = useState(false);

  const [loginAttempts, setLoginAttempts] = useState(0);
  const MAX_LOGIN_ATTEMPTS = 3;

  const resetForm = () => {
    setEmail('');
    setPassword('');
    setFirstName('');
    setLastName('');
    setShowPassword(false);
    setRememberMe(false);
    setErrorMessage('');
    setEmailError('');
    setPasswordError('');
    setFirstNameError('');
    setLastNameError('');
    setShakeError(false);
  };

  const switchView = (newView: DialogView) => {
    setErrorMessage('');
    setEmailError('');
    setPasswordError('');
    setFirstNameError('');
    setLastNameError('');
    setShakeError(false);
    setView(newView);
  };

  const showError = (message: string) => {
    setErrorMessage(message);
    setShakeError(true);
    setTimeout(() => setShakeError(false), 500);
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage('');
    setEmailError('');
    setPasswordError('');

    let hasError = false;
    if (!email.trim()) { setEmailError('Email is required'); hasError = true; }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setEmailError('Please enter a valid email'); hasError = true; }
    
    if (!password.trim()) { setPasswordError('Password is required'); hasError = true; }
    
    if (hasError) {
      showError('Please fix the errors above');
      return;
    }

    const result = await login(email, password);
    if (result.success) {
      setLoginAttempts(0); // Reset attempts on success
      toast.success('Welcome back!');
      resetForm();
      onOpenChange(false);
      navigate('/dashboard');
    } else {
      const newAttempts = loginAttempts + 1;
      setLoginAttempts(newAttempts);
      
      let errorMsg = result.error || 'Login failed. Please try again.';
      if (errorMsg.toLowerCase().includes('invalid')) {
        errorMsg = `Incorrect password. Attempt ${newAttempts} of ${MAX_LOGIN_ATTEMPTS}.`;
        if (newAttempts >= MAX_LOGIN_ATTEMPTS) {
          errorMsg = 'Too many failed attempts. Please reset your password.';
        }
        setPassword('');
        setPasswordError('Incorrect password');
      }
      if (errorMsg.toLowerCase().includes('not configured') || errorMsg.toLowerCase().includes('user not found')) {
        errorMsg = 'Account not found. Please check your email.';
        setEmailError('Account not found');
      }
      showError(errorMsg);
    }
  };

  const handleSignUp = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage('');

    if (!firstName.trim()) { setErrorMessage('First name is required'); return; }
    if (!lastName.trim()) { setErrorMessage('Last name is required'); return; }
    if (!email.trim()) { setErrorMessage('Email is required'); return; }
    if (!password.trim()) { setErrorMessage('Password is required'); return; }
    if (password.length < 6) { setErrorMessage('Password must be at least 6 characters'); return; }

    const result = await signUp(email, password, firstName, lastName);
    if (result.success) {
      switchView('check-email');
    } else {
      setErrorMessage(result.error || 'Sign up failed. Please try again.');
    }
  };

  const handleForgotPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage('');

    if (!email.trim()) { setErrorMessage('Email is required'); return; }

    const result = await resetPassword(email);
    if (result.success) {
      switchView('check-email');
    } else {
      setErrorMessage(result.error || 'Failed to send reset email.');
    }
  };

  const handleOpenChange = (isOpen: boolean) => {
    if (!isOpen) {
      resetForm();
      setView('login');
      setLoginAttempts(0); // Reset attempts counter on close
    }
    onOpenChange(isOpen);
  };

  const headerConfig = {
    login: { title: 'Sign In', subtitle: 'Welcome back to Nytro' },
    signup: { title: 'Create Account', subtitle: 'Get started with Nytro' },
    forgot: { title: 'Reset Password', subtitle: 'We\'ll send you a reset link' },
    'check-email': { title: 'Check Your Email', subtitle: 'We\'ve sent you a message' },
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="sm:max-w-[420px] p-0 overflow-hidden bg-white rounded-xl shadow-xl border-[#3b82f6]/20">
        {/* Clean white header */}
        <div className="px-8 pt-8 pb-4">
          <div className="flex items-start justify-between">
            <div>
              <DialogTitle className="text-2xl font-semibold text-[#1e293b]">
                {headerConfig[view].title}
              </DialogTitle>
              <p className="text-[#64748b] mt-1 text-base">
                {headerConfig[view].subtitle}
              </p>
            </div>
            {view !== 'login' && (
              <button
                type="button"
                onClick={() => switchView('login')}
                className="text-[#94a3b8] hover:text-[#64748b] transition-colors p-1"
              >
                <X className="h-5 w-5" />
              </button>
            )}
          </div>
        </div>

        {/* Content */}
        <div className="px-8 pb-8">
          {errorMessage && (
            <div className={`flex items-start gap-2 p-3 mb-4 rounded-lg bg-red-50 border border-red-200 ${shakeError ? 'animate-shake' : ''}`}>
              <AlertCircle className="h-4 w-4 text-red-500 flex-shrink-0 mt-0.5" />
              <p className="text-sm text-red-600 font-medium">{errorMessage}</p>
            </div>
          )}

          {/* LOGIN VIEW */}
          {view === 'login' && (
            <form onSubmit={handleLogin} className="space-y-5">
              <div>
                <Label htmlFor="login-email" className="text-[#1e293b] font-medium text-sm">Email</Label>
                <Input
                  id="login-email"
                  type="email"
                  placeholder="you@example.com"
                  value={email}
                  onChange={(e) => { setEmail(e.target.value); setEmailError(''); setErrorMessage(''); }}
                  className={`mt-2 h-11 rounded-lg border-[#3b82f6]/20 focus:border-[#3b82f6] focus:ring-2 focus:ring-[#3b82f6]/20 ${emailError ? 'border-red-300 focus:border-red-500 focus:ring-red-200' : ''}`}
                  autoComplete="email"
                />
                {emailError && <p className="text-xs text-red-500 mt-1.5">{emailError}</p>}
              </div>

              <div>
                <Label htmlFor="login-password" className="text-[#1e293b] font-medium text-sm">Password</Label>
                <div className="relative mt-2">
                  <Input
                    id="login-password"
                    type={showPassword ? 'text' : 'password'}
                    placeholder="••••••••"
                    value={password}
                    onChange={(e) => { setPassword(e.target.value); setPasswordError(''); setErrorMessage(''); }}
                    className={`h-11 rounded-lg border-[#3b82f6]/20 focus:border-[#3b82f6] focus:ring-2 focus:ring-[#3b82f6]/20 pr-11 ${passwordError ? 'border-red-300 focus:border-red-500 focus:ring-red-200' : ''}`}
                    autoComplete="current-password"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#64748b] transition-colors"
                  >
                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
                {passwordError && <p className="text-xs text-red-500 mt-1.5">{passwordError}</p>}
              </div>

              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="remember-me"
                    checked={rememberMe}
                    onCheckedChange={(checked) => setRememberMe(checked === true)}
                    className="border-[#cbd5e1] data-[state=checked]:bg-[#3b82f6] data-[state=checked]:border-[#3b82f6]"
                  />
                  <Label htmlFor="remember-me" className="text-sm text-[#64748b] font-normal cursor-pointer">
                    Remember me
                  </Label>
                </div>
                <button
                  type="button"
                  onClick={() => switchView('forgot')}
                  className="text-sm text-[#3b82f6] hover:text-[#2563eb] font-medium"
                >
                  Forgot password?
                </button>
              </div>

              <Button
                type="submit"
                className="w-full bg-[#3b82f6] hover:bg-[#2563eb] text-white font-semibold py-3 h-auto rounded-lg text-base"
                disabled={isLoading}
              >
                {isLoading ? (
                  <span className="flex items-center justify-center gap-2">
                    <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    Signing in...
                  </span>
                ) : 'Sign In'}
              </Button>

              {/* Reset Password button after 3 failed attempts */}
              {loginAttempts >= MAX_LOGIN_ATTEMPTS && (
                <div className="space-y-3 pt-2">
                  <div className="h-px bg-[#e2e8f0]" />
                  <Button
                    type="button"
                    onClick={() => switchView('forgot')}
                    variant="outline"
                    className="w-full border-red-200 text-red-600 hover:bg-red-50 font-semibold py-3 h-auto rounded-lg"
                  >
                    Reset Password
                  </Button>
                  <p className="text-center text-xs text-red-500">
                    Too many failed attempts. Reset your password to continue.
                  </p>
                </div>
              )}

              <p className="text-center text-sm text-[#64748b] pt-2">
                Don't have an account?{' '}
                <button type="button" onClick={() => switchView('signup')} className="text-[#3b82f6] font-semibold hover:underline">
                  Create account
                </button>
              </p>
            </form>
          )}

          {/* SIGN UP VIEW */}
          {view === 'signup' && (
            <form onSubmit={handleSignUp} className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="signup-first" className="text-[#1e293b] font-semibold text-sm">First Name</Label>
                  <Input
                    id="signup-first"
                    type="text"
                    placeholder="John"
                    value={firstName}
                    onChange={(e) => { setFirstName(e.target.value); setErrorMessage(''); }}
                    className="mt-1.5 border-[#dbeafe] focus:border-[#3b82f6] h-10"
                    autoComplete="given-name"
                  />
                </div>
                <div>
                  <Label htmlFor="signup-last" className="text-[#1e293b] font-semibold text-sm">Last Name</Label>
                  <Input
                    id="signup-last"
                    type="text"
                    placeholder="Smith"
                    value={lastName}
                    onChange={(e) => { setLastName(e.target.value); setErrorMessage(''); }}
                    className="mt-1.5 border-[#dbeafe] focus:border-[#3b82f6] h-10"
                    autoComplete="family-name"
                  />
                </div>
              </div>

              <div>
                <Label htmlFor="signup-email" className="text-[#1e293b] font-semibold text-sm">Email</Label>
                <Input
                  id="signup-email"
                  type="email"
                  placeholder="you@example.com"
                  value={email}
                  onChange={(e) => { setEmail(e.target.value); setErrorMessage(''); }}
                  className="mt-1.5 border-[#dbeafe] focus:border-[#3b82f6] h-10"
                  autoComplete="email"
                />
              </div>

              <div>
                <Label htmlFor="signup-password" className="text-[#1e293b] font-semibold text-sm">Password</Label>
                <div className="relative mt-1.5">
                  <Input
                    id="signup-password"
                    type={showPassword ? 'text' : 'password'}
                    placeholder="Min. 6 characters"
                    value={password}
                    onChange={(e) => { setPassword(e.target.value); setErrorMessage(''); }}
                    className="border-[#dbeafe] focus:border-[#3b82f6] pr-10 h-10"
                    autoComplete="new-password"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#3b82f6] transition-colors"
                  >
                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </div>

              <Button
                type="submit"
                className="w-full bg-[#3b82f6] hover:bg-[#2563eb] text-white font-semibold py-2.5 h-auto rounded-lg"
                disabled={isLoading}
              >
                {isLoading ? (
                  <span className="flex items-center gap-2">
                    <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    Creating account...
                  </span>
                ) : 'Create Account'}
              </Button>

              <p className="text-center text-sm text-[#64748b]">
                Already have an account?{' '}
                <button type="button" onClick={() => switchView('login')} className="text-[#3b82f6] font-medium hover:underline">
                  Sign in
                </button>
              </p>
            </form>
          )}

          {/* FORGOT PASSWORD VIEW */}
          {view === 'forgot' && (
            <form onSubmit={handleForgotPassword} className="space-y-5">
              <p className="text-sm text-[#64748b]">
                Enter the email address associated with your account and we'll send you a link to reset your password.
              </p>

              <div>
                <Label htmlFor="forgot-email" className="text-[#1e293b] font-semibold text-sm">Email</Label>
                <Input
                  id="forgot-email"
                  type="email"
                  placeholder="you@example.com"
                  value={email}
                  onChange={(e) => { setEmail(e.target.value); setErrorMessage(''); }}
                  className="mt-1.5 border-[#dbeafe] focus:border-[#3b82f6] h-10"
                  autoComplete="email"
                />
              </div>

              <Button
                type="submit"
                className="w-full bg-[#3b82f6] hover:bg-[#2563eb] text-white font-semibold py-2.5 h-auto rounded-lg"
                disabled={isLoading}
              >
                {isLoading ? (
                  <span className="flex items-center gap-2">
                    <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    Sending...
                  </span>
                ) : 'Send Reset Link'}
              </Button>
            </form>
          )}

          {/* CHECK EMAIL VIEW */}
          {view === 'check-email' && (
            <div className="text-center py-4 space-y-4">
              <div className="w-16 h-16 rounded-full bg-[#dbeafe] flex items-center justify-center mx-auto">
                <Mail className="h-8 w-8 text-[#3b82f6]" />
              </div>
              <div>
                <p className="text-[#1e293b] font-semibold">Check your inbox</p>
                <p className="text-sm text-[#64748b] mt-1">
                  We've sent an email to <span className="font-medium text-[#1e293b]">{email}</span>.
                  Please follow the instructions in the email to continue.
                </p>
              </div>
              <Button
                onClick={() => { resetForm(); switchView('login'); }}
                variant="outline"
                className="border-[#dbeafe] text-[#3b82f6] hover:bg-[#eff6ff]"
              >
                Back to Sign In
              </Button>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
