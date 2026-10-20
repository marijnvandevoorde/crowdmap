import { Component, ChangeDetectionStrategy } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-privacy',
  imports: [RouterLink],
  templateUrl: './privacy.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './privacy.scss',
})
export class PrivacyComponent {}
